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
use Rollerworks\Component\Search\Exception\OrderStructureException;
use Rollerworks\Component\Search\Exception\StringLexerException;
use Rollerworks\Component\Search\Exception\UnexpectedTypeException;
use Rollerworks\Component\Search\Exception\UnknownFieldException;
use Rollerworks\Component\Search\Field\FieldConfig;
use Rollerworks\Component\Search\Field\OrderField;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\SearchOrder;
use Rollerworks\Component\Search\StructureBuilder;
use Rollerworks\Component\Search\Value\ValuesGroup;

/**
 * StringInput - processes input in the StringQuery format.
 *
 * The formats works as follow (whitespace between values is ignored).
 *
 * Caution: The error message reports the character position not the byte position.
 * Multi byte may cause some problems when using substr() rather then mb_substr().
 *
 * Each query-pair is a 'field-name: value1, value2;' or 'value1, value2;'.
 *
 * Tip: The field-name can be omitted, which uses the default field-name configured
 * in the ProcessorConfig. This should only be used for the first values list.
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
 *  must be surrounded by quotes or use a custom value lexer.
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
     * @var StructureBuilder|null
     */
    protected $structureBuilder;

    /**
     * @var StructureBuilder|null
     */
    protected $orderStructureBuilder;

    /**
     * @var string[]
     */
    protected $fields = [];

    /**
     * @var \Closure[]
     */
    protected $valueLexers = [];

    /** @var StringLexer */
    private $lexer;

    public function __construct(?Validator $validator = null)
    {
        $this->lexer = new StringLexer();
        parent::__construct($validator);
    }

    /**
     * @param string $input
     *
     * @throws InvalidSearchConditionException
     */
    public function process(ProcessorConfig $config, $input): SearchCondition
    {
        if (! \is_string($input)) {
            throw new UnexpectedTypeException($input, 'string');
        }

        $input = trim($input);

        $fieldSet = $config->getFieldSet();
        $condition = null;
        $this->valueLexers = [];
        $this->errors = new ErrorList();
        $this->config = $config;
        $this->level = 0;

        $this->initForProcess($config);

        try {
            $this->parse($config, $input, $fieldSet);
            $condition = new SearchCondition($fieldSet, $this->structureBuilder->getRootGroup());

            $orderValuesGroup = $this->orderStructureBuilder->getRootGroup();

            if ($orderValuesGroup->countValues() > 0) {
                $condition->setOrder(new SearchOrder($orderValuesGroup));
            }
            $this->assertLevel0();
        } catch (InputProcessorException $e) {
            $this->errors[] = $e->toErrorMessageObj();
        } finally {
            $this->structureBuilder = null;
        }

        if (\count($this->errors)) {
            $errors = $this->errors->getArrayCopy();

            throw new InvalidSearchConditionException($errors);
        }

        return $condition;
    }

    abstract protected function initForProcess(ProcessorConfig $config): void;

    private function getFieldName(string $name): string
    {
        if (isset($this->fields[$name])) {
            return $this->fields[$name];
        }

        throw new UnknownFieldException($name);
    }

    final protected function parse(ProcessorConfig $config, string $input, FieldSet $fieldSet): void
    {
        $this->config = $config;
        $this->lexer->parse($input, $this->valueLexers);

        // If the first part is a group then ignore the match, let fieldValuesPairs() handle the
        // group logical on this own.
        if (! $this->lexer->isGlimpse('/[*&]?\s*\(/A')) {
            $logical = $this->lexer->matchOptional('*') !== null ? ValuesGroup::GROUP_LOGICAL_OR : ValuesGroup::GROUP_LOGICAL_AND;
            $this->structureBuilder->getRootGroup()->setGroupLogical($logical);
        }

        $this->lexer->skipEmptyLines();
        $this->fieldValuesPairs();

        if (! $this->orderStructureBuilder->getRootGroup()->countValues()) {
            /** @var FieldConfig $field */
            foreach ($fieldSet->all() as $name => $field) {
                if (OrderField::isOrder($name) && null !== $direction = $field->getOption('default')) {
                    $this->orderStructureBuilder->field($name, '[order][%s]');
                    $this->orderStructureBuilder->simpleValue($direction, '');
                    $this->orderStructureBuilder->endValues();
                }
            }
        }
    }

    private function fieldValuesPairs(bool $inGroup = false): void
    {
        while (! $this->lexer->isEnd()) {
            if ($this->lexer->isGlimpse('/[*&]?\s*\(/A')) {
                ++$this->level;
                $this->fieldGroup();
                --$this->level;

                continue;
            }

            if ($this->lexer->isGlimpse('/[*&]/A')) {
                throw $this->lexer->createFormatException(StringLexerException::GROUP_LOGICAL_WITHOUT_GROUP);
            }

            if ($this->lexer->isGlimpse(')')) {
                if ($inGroup) {
                    break;
                }

                throw $this->lexer->createFormatException(StringLexerException::CANNOT_CLOSE_UNOPENED_GROUP);
            }

            if ($this->lexer->isGlimpse(StringLexer::FIELD_NAME)) {
                $fieldName = $this->getFieldName($this->lexer->fieldIdentification());
            } else {
                $fieldName = $this->config->getDefaultField(true);
            }

            if (OrderField::isOrder($fieldName) && $inGroup) {
                throw OrderStructureException::noGrouping();
            }

            $this->lexer->skipEmptyLines();
            $this->fieldValues($fieldName);
            $this->lexer->skipEmptyLines();
        }
    }

    private function fieldGroup(): void
    {
        $logical = $this->lexer->matchOptional('*') !== null ? ValuesGroup::GROUP_LOGICAL_OR : ValuesGroup::GROUP_LOGICAL_AND;
        $this->structureBuilder->enterGroup($logical, '[%d]');

        $this->lexer->skipWhitespace();
        $this->lexer->expects('(');
        $this->lexer->skipEmptyLines();

        $this->fieldValuesPairs(true);

        $this->lexer->expects(')');
        $this->lexer->skipEmptyLines();

        $this->structureBuilder->leaveGroup();

        $this->lexer->matchOptional(';');
        $this->lexer->skipEmptyLines();
    }

    private function fieldValues(string $name): void
    {
        $isOrder = OrderField::isOrder($name);

        /** @var StructureBuilder $structureBuilder */
        $structureBuilder = $isOrder ? $this->orderStructureBuilder : $this->structureBuilder;

        $structureBuilder->field($name, '[%s]');

        $hasValues = false;
        $pathVal = '[{pos}]';

        while (! $this->lexer->isEnd() && ! $this->lexer->isGlimpse('/[);]/A')) {
            $valueType = $this->lexer->detectValueType($name);

            switch ($valueType) {
                case StringLexer::COMPARE:
                    [$operator, $value] = $this->lexer->comparisonValue($name);
                    $structureBuilder->comparisonValue($operator, $value, [$pathVal, '', '']);

                    break;

                case StringLexer::PATTERN_MATCH:
                    [$caseInsensitive, $type, $value] = $this->lexer->patternMatchValue();
                    $structureBuilder->patterMatchValue($type, $value, $caseInsensitive, [$pathVal, '', '']);

                    break;

                case StringLexer::RANGE:
                    $negative = $this->lexer->matchOptional('!') !== null;
                    [$lowerInclusive, $lowerBound, $upperBound, $upperInclusive] = $this->lexer->rangeValue($name);

                    if ($negative) {
                        $structureBuilder->excludedRangeValue(
                            $lowerBound,
                            $upperBound,
                            $lowerInclusive,
                            $upperInclusive,
                            [$pathVal, '[lower]', '[upper]']
                        );
                    } else {
                        $structureBuilder->rangeValue(
                            $lowerBound,
                            $upperBound,
                            $lowerInclusive,
                            $upperInclusive,
                            [$pathVal, '[lower]', '[upper]']
                        );
                    }

                    break;

                case StringLexer::SIMPLE_VALUE:
                    if ($this->lexer->matchOptional('!') !== null) {
                        $structureBuilder->excludedSimpleValue($this->lexer->valuePart($name), $pathVal);
                    } else {
                        $structureBuilder->simpleValue($this->lexer->valuePart($name), $pathVal);
                    }

                    break;
            }

            if ($this->lexer->matchOptional(',') !== null && $this->lexer->isGlimpse(';')) {
                throw $this->lexer->createFormatException(StringLexerException::INCORRECT_VALUES_SEPARATOR);
            }

            $this->lexer->skipEmptyLines();

            // We got here, so no errors.
            $hasValues = true;
        }

        if (! $hasValues) {
            throw $this->lexer->createFormatException(StringLexerException::FIELD_REQUIRES_VALUES);
        }

        $structureBuilder->endValues();
        $this->lexer->matchOptional(';');
    }
}
