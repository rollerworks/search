<?php

declare(strict_types=1);

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Input;

use Rollerworks\Component\Search\ErrorList;
use Rollerworks\Component\Search\Exception\InputProcessorException;
use Rollerworks\Component\Search\Exception\InvalidSearchConditionException;
use Rollerworks\Component\Search\Exception\StringLexerException;
use Rollerworks\Component\Search\Exception\UnexpectedTypeException;
use Rollerworks\Component\Search\Exception\UnknownFieldException;
use Rollerworks\Component\Search\Field\FieldConfig;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\Value\ValuesBag;
use Rollerworks\Component\Search\Value\ValuesGroup;

/**
 * StringInput - processes input in the StringQuery format.
 *
 * The formats works as follow (whitespace between values is ignored).
 *
 * Caution: The error message reports the character position not the byte position.
 * Multi byte may cause some problems when using substr() rather then mb_substr().
 *
 * Each query-pair is a 'field-name: value1, value2;'.
 *
 *  Query-pairs can be nested inside a group "(field-name: value1, value2;)"
 *    Subgroups are threaded as AND-case to there parent,
 *    multiple groups inside the same group are OR-case to each other.
 *
 *    By default all the query-pairs and other direct-subgroups are treated as AND-case.
 *    To make a group OR-case (any of the fields), prefix the group with '*'
 *    Example: *(field1=values; field2=values);
 *
 *    Groups are separated with a single semicolon ";".
 *    If the subgroup is last in the group the semicolon can be omitted.
 *
 *  Query-Pairs are separated with a single semicolon ";"
 *  If the query-pair is last in the group the semicolon can be omitted.
 *
 *  Each value inside a query-pair is separated with a single comma.
 *  A value containing special characters (<>[](),;~!*?=) or spaces
 *  must be surrounded by quotes.
 *
 *  Note surrounding spaces are ignored. Example: field: value , value2  ;
 *
 *  To escape a quote use it double.
 *  Example: field: "va""lue";
 *
 *  Escaped quotes will be normalized to a single one.
 *
 * Line separators are allowed for better readability, but are not allowed
 * within a value.
 *
 * Ranges
 * ======
 *
 * A range consists of two sides, lower and upper bound (inclusive by default).
 * Each side is considered a value-part and must follow the value convention (as described above).
 *
 * Example: field: 1~100; field2: -1 ~ 100
 *
 * Each side is inclusive by default, meaning 'the value' and anything lower/higher then it.
 * The left delimiter can be `[` (inclusive) or `]` (exclusive).
 * The right delimiter can be `[` (exclusive) or `]` (inclusive).
 *
 *   `]1 ~ 100`  is equal to (> 1 and <= 100)
 *   `[1 ~ 100`  is equal to (>= 1 and <= 100)
 *   `[1 ~ 100[` is equal to (>= 1 and < 100)
 *   `]1 ~ 100[` is equal to (> 1 and < 100)
 *
 *   Example:
 *     field: ]1 ~ 100;
 *     field: [1 ~ 100;
 *
 * Excluded values
 * ===============
 *
 * To mark a value as excluded (also done for ranges) prefix it with an '!'.
 *
 * Example: field: !value, !1 ~ 10;
 *
 * Comparison
 * ==========
 *
 * Comparisons are as any programming language.
 * Supported operators are: <, <=, <>, >, >=
 *
 * Followed by a value-part.
 *
 * Example: field: >= 1, < -10;
 *
 * Caution: Spaces are not allowed within the operator.
 * Invalid: > =
 *
 * PatternMatch
 * ============
 *
 * PatternMatch works similar to Comparison, everything that starts with a tilde (~)
 * is considered a pattern match. Spaces within the operator are not allowed.
 *
 * Supported operators are:
 *
 *    ~* (contains)
 *    ~> (starts with)
 *    ~< (ends with)
 *
 * And not the NOT equivalent.
 *
 *     ~!* (does not contain)
 *     ~!> (does not start with)
 *     ~!< (does not end with)
 *
 * Example: field: ~> foo, ~*"bar";
 *
 * To mark the pattern case insensitive add an 'i' directly after the '~'.
 *
 * Example: field: ~i> foo, ~i!* "bar";
 */
abstract class StringInput extends AbstractInput
{
    /**
     * @var FieldValuesFactory|null
     */
    protected $valuesFactory;

    /**
     * @var string[]
     */
    protected $fields = [];

    private $lexer;

    public function __construct(Validator $validator = null)
    {
        $this->lexer = new StringLexer();
        parent::__construct($validator);
    }

    /**
     * Process the input and returns the result.
     *
     * @param ProcessorConfig $config
     * @param string          $input
     *
     * @throws InvalidSearchConditionException
     *
     * @return SearchCondition
     */
    public function process(ProcessorConfig $config, $input): SearchCondition
    {
        if (!is_string($input)) {
            throw new UnexpectedTypeException($input, 'string');
        }

        $input = trim($input);

        if ('' === $input) {
            return new SearchCondition($config->getFieldSet(), new ValuesGroup());
        }

        $condition = null;
        $this->errors = new ErrorList();
        $this->config = $config;
        $this->level = 0;

        $this->initForProcess($config);

        try {
            $condition = new SearchCondition($config->getFieldSet(), $this->parse($config, $input));
            $this->assertLevel0();
        } catch (InputProcessorException $e) {
            $this->errors[] = $e->toErrorMessageObj();
        } finally {
            $this->valuesFactory = null;
        }

        if (count($this->errors)) {
            $errors = $this->errors->getArrayCopy();

            throw new InvalidSearchConditionException($errors);
        }

        return $condition;
    }

    /**
     * Initialize the fields ValuesFactory.
     *
     * @param ProcessorConfig $config
     */
    abstract protected function initForProcess(ProcessorConfig $config): void;

    private function getFieldName(string $name): string
    {
        if (isset($this->fields[$name])) {
            return $this->fields[$name];
        }

        throw new UnknownFieldException($name);
    }

    final protected function parse(ProcessorConfig $config, string $input): ValuesGroup
    {
        $this->config = $config;
        $this->lexer->parse($input);

        if (null !== $this->lexer->matchOptional('*')) {
            $valuesGroup = new ValuesGroup(ValuesGroup::GROUP_LOGICAL_OR);
        } else {
            $valuesGroup = new ValuesGroup(ValuesGroup::GROUP_LOGICAL_AND);
        }

        $this->lexer->skipEmptyLines();

        $this->fieldValuesPairs($valuesGroup);

        return $valuesGroup;
    }

    private function fieldValuesPairs(ValuesGroup $valuesGroup, string $path = '', bool $inGroup = false)
    {
        $groupCount = 0;

        while (!$this->lexer->isEnd()) {
            if ($this->lexer->isGlimpse('/[*&]?\s*\(/A')) {
                $this->validateGroupsCount($groupCount + 1, $path);

                ++$groupCount;
                ++$this->level;

                $valuesGroup->addGroup($this->fieldGroup($path.'['.$groupCount.']'));

                --$this->level;

                continue;
            } elseif ($this->lexer->isGlimpse('/[*&]/A')) {
                throw $this->lexer->createFormatException(StringLexerException::GROUP_LOGICAL_WITHOUT_GROUP);
            }

            if ($this->lexer->isGlimpse(')')) {
                if ($inGroup) {
                    break;
                }

                throw $this->lexer->createFormatException(StringLexerException::CANNOT_CLOSE_UNOPENED_GROUP);
            }

            $fieldName = $this->getFieldName($this->lexer->fieldIdentification());
            $fieldConfig = $this->config->getFieldSet()->get($fieldName);

            $this->lexer->skipEmptyLines();
            $valuesGroup->addField(
                $fieldName,
                $this->fieldValues($fieldConfig, new ValuesBag(), $path)
            );

            $this->lexer->skipEmptyLines();
        }
    }

    private function fieldGroup(string $path = ''): ValuesGroup
    {
        $this->validateGroupNesting($path);

        if (null !== $this->lexer->matchOptional('*')) {
            $valuesGroup = new ValuesGroup(ValuesGroup::GROUP_LOGICAL_OR);
        } else {
            $valuesGroup = new ValuesGroup(ValuesGroup::GROUP_LOGICAL_AND);
        }

        $this->lexer->skipWhitespace();
        $this->lexer->expects('(');
        $this->lexer->skipEmptyLines();

        $this->fieldValuesPairs($valuesGroup, $path, true);

        $this->lexer->expects(')');
        $this->lexer->skipEmptyLines();

        $this->lexer->matchOptional(';');
        $this->lexer->skipEmptyLines();

        return $valuesGroup;
    }

    private function fieldValues(FieldConfig $field, ValuesBag $valuesBag, string $path)
    {
        $hasValues = false;
        $this->valuesFactory->initContext($field, $valuesBag, $path);

        $pathVal = '['.$field->getName().'][%d]';

        while (!$this->lexer->isEnd() && !$this->lexer->isGlimpse('/[);]/A')) {
            $valueType = $this->lexer->detectValueType();

            switch ($valueType) {
                case StringLexer::COMPARE:
                    list($operator, $value) = $this->lexer->comparisonValue();
                    $this->valuesFactory->addComparisonValue($operator, $value, [$pathVal, '', '']);
                    break;

                    case StringLexer::PATTERN_MATCH:
                    list($caseInsensitive, $type, $value) = $this->lexer->patternMatchValue();
                    $this->valuesFactory->addPatterMatch($type, $value, $caseInsensitive, [$pathVal, '', '']);
                    break;

                case StringLexer::RANGE:
                    $negative = null !== $this->lexer->matchOptional('!');
                    list($lowerInclusive, $lowerBound, $upperBound, $upperInclusive) = $this->lexer->rangeValue();

                    if ($negative) {
                        $this->valuesFactory->addExcludedRange(
                            $lowerBound,
                            $upperBound,
                            $lowerInclusive,
                            $upperInclusive,
                            [$pathVal, '[lower]', '[upper]']
                        );
                    } else {
                        $this->valuesFactory->addRange(
                            $lowerBound,
                            $upperBound,
                            $lowerInclusive,
                            $upperInclusive,
                            [$pathVal, '[lower]', '[upper]']
                        );
                    }
                    break;

                case StringLexer::SIMPLE_VALUE:
                    $negative = null !== $this->lexer->matchOptional('!');
                    if ($negative) {
                        $this->valuesFactory->addExcludedSimpleValue($this->lexer->stringValue(), $pathVal);
                    } else {
                        $this->valuesFactory->addSimpleValue($this->lexer->stringValue(), $pathVal);
                    }
                    break;
            }

            if (null !== $this->lexer->matchOptional(',') && $this->lexer->isGlimpse(';')) {
                throw $this->lexer->createFormatException(StringLexerException::INCORRECT_VALUES_SEPARATOR);
            }

            $this->lexer->skipEmptyLines();

            // We got here, so no errors.
            $hasValues = true;
        }

        if (!$hasValues) {
            throw $this->lexer->createFormatException(StringLexerException::FIELD_REQUIRES_VALUES);
        }

        $this->lexer->matchOptional(';');

        return $valuesBag;
    }
}
