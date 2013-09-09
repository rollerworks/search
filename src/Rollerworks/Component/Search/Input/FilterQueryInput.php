<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search\Input;

use Rollerworks\Component\Search\Exception\FieldRequiredException;
use Rollerworks\Component\Search\Exception\ValuesOverflowException;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\Input\FilterQuery\Lexer;
use Rollerworks\Component\Search\Input\FilterQuery\QueryException;
use Rollerworks\Component\Search\Value;
use Rollerworks\Component\Search\ValuesBag;
use Rollerworks\Component\Search\ValuesGroup;

/**
 * SearchQuery - processes input in the SearchQuery format.
 *
 * The formats works as follow (spaced are ignored).
 *
 * Every query-pair is a 'field-name: value1, value2;'.
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
 *  When the value contains special characters or spaces it must be quoted.
 *   Numbers only need to be quoted when there marked negative "-123".
 *
 *  To escape a quote use it double.
 *  Example: field: "va""lue";
 *
 *  Escaped quotes will be normalized to a single one.
 *
 * Ranges
 * ======
 *
 * A range consists of two sides, lower and upper bound (inclusive by default).
 * Each side is considered a value-part and must follow the value convention (as described above).
 *
 * Example: field: 1-100; field2: "-1" - 100
 *
 * Each side is inclusive by default, meaning 'the value' and anything lower/higher then it.
 * To mark a value exclusive (everything between, but not the actual value) prefix it with ']'.
 *
 * You can also the use '[' to mark it inclusive (explicitly).
 *
 *    ]1-100 is equal to (> 1 and <= 100)
 *    [1-100 is equal to (>= 1 and <= 100)
 *    [1-100[ is equal to (>= 1 and < 100)
 *    ]1-100[ is equal to (> 1 and < 100)
 *
 *   Example:
 *     field: ]1 - 100;
 *     field: [1 - 100;
 *
 * Excluded values
 * ===============
 *
 * To mark a value as excluded (also done for ranges) prefix it with an '!'.
 *
 * Example: field: !value, !1 - 10;
 *
 * Comparison
 * ==========
 *
 * Comparisons are very simple.
 * Supported operators are: <, <=, <>, >, >=
 *
 * Followed by a value-part.
 *
 * Example: field: >1=, < "-10";
 *
 * PatternMatch
 * ============
 *
 * PatternMatch works similar to Comparison,
 * everything that starts with tilde (~) is considered a pattern match.
 *
 * Supported operators are:
 *
 *    ~* (contains)
 *    ~> (starts with)
 *    ~< (ends with)
 *    ~? (regex matching)
 *
 * And not the NOT equivalent.
 *
 *     ~!* (does not contain)
 *     ~!> (does not start with)
 *     ~!< (does not end with)
 *     ~!? (does not match regex)
 *
 * Example: field: ~>foo, ~*"bar", ~?"^foo|bar$";
 *
 * To mark the pattern case insensitive add an 'i' directly after the '~'.
 *
 * Example: field: ~i>foo, ~i!*"bar", ~i?"^foo|bar$";
 *
 * Note: The regex is limited to simple POSIX expressions.
 * Actual usage is handled by the storage layer, and may not fully support complex expressions.
 *
 * Caution: Regex delimiters are not used.
 */
class FilterQueryInput extends AbstractInput
{
    /**
     * @var Lexer
     */
    private $lexer;

    /**
     * @var string
     */
    private $input;

    /**
     * Creates a new query parser object.
     */
    public function __construct()
    {
        $this->lexer = new Lexer();
    }

    /**
     * Gets the lexer used by the parser.
     *
     * @return Lexer
     */
    public function getLexer()
    {
        return $this->lexer;
    }

    /**
     * Frees this parser, enabling it to be reused.
     *
     * @param boolean $deep     Whether to clean peek and reset errors.
     * @param integer $position Position to reset.
     */
    public function free($deep = false, $position = 0)
    {
        // WARNING! Use this method with care. It resets the scanner!
        $this->lexer->resetPosition($position);

        // Deep = true cleans peek and also any previously defined errors
        if ($deep) {
            $this->lexer->resetPeek();
        }

        $this->lexer->token = null;
        $this->lexer->lookahead = null;
    }

    /**
     * Process the input and returns the result.
     *
     * @param string $input
     *
     * @return null|ValuesGroup Returns null on empty input
     */
    public function process($input)
    {
        $input = trim($input);

        if (empty($input)) {
            return null;
        }

        $this->lexer->setInput($input);
        $this->lexer->moveNext();

        $valuesGroup = new ValuesGroup();
        $this->FieldValuesPairs($valuesGroup, 0);

        return $valuesGroup;
    }

    /**
     * Attempts to match the given token with the current lookahead token.
     *
     * If they match, updates the lookahead token; otherwise raises a syntax
     * error.
     *
     * @param int $token The token type.
     *
     * @throws QueryException If the tokens don't match.
     */
    private function match($token)
    {
        $lookaheadType = $this->lexer->lookahead['type'];

        // short-circuit on first condition, usually types match
        if ($lookaheadType !== $token && $token !== Lexer::T_IDENTIFIER && $lookaheadType <= Lexer::T_IDENTIFIER) {
            $this->syntaxError($this->lexer->getLiteral($token));
        }

        $this->lexer->moveNext();
    }

    /**
     * Generates a new syntax error.
     *
     * @param string     $expected Expected string.
     * @param array|null $token    Got token.
     *
     * @throws QueryException
     */
    private function syntaxError($expected = '', $token = null)
    {
        if ($token === null) {
            $token = $this->lexer->lookahead;
        }

        $tokenPos = (isset($token['position'])) ? $token['position'] : '-1';

        $message = "line 0, col {$tokenPos}: Error: ";
        $message .= ($expected !== '') ? "Expected {$expected}, got " : 'Unexpected ';
        $message .= ($this->lexer->lookahead === null) ? 'end of string.' : "'{$token['value']}'";

        throw QueryException::syntaxError($message, new QueryException($this->input));
    }

    /**
     * Group ::= {"(" {Group}* FieldValuesPairs {";" Group}* ")" | "(" FieldValuesPairs ";" FieldValuesPairs {";" Group}* ")" [ ";" ] | {Group}+ [ ";" ]}+
     *
     * @param integer $level
     * @param integer $idx
     *
     * @return ValuesGroup
     */
    private function Group($level = 0, $idx = 0)
    {
        $this->validateGroupNesting($idx, $level);

        $valuesGroup = new ValuesGroup();

        if ($this->lexer->isNextToken(Lexer::T_MULTIPLY)) {
            $this->match(Lexer::T_MULTIPLY);

            $valuesGroup->setGroupLogical(ValuesGroup::GROUP_LOGICAL_OR);
        }
        $this->match(Lexer::T_OPEN_PARENTHESIS);

        // if there is a subgroup the FieldValuesPairs() method will handle it
        $this->FieldValuesPairs($valuesGroup, $level, $idx, true);

        $this->match(Lexer::T_CLOSE_PARENTHESIS);

        if (null !== $this->lexer->lookahead && $this->lexer->isNextToken(Lexer::T_SEMICOLON)) {
            $this->match(Lexer::T_SEMICOLON);
        }

        return $valuesGroup;
    }

    /**
     * {FieldIdentification ":" FieldValues}*
     *
     * @param ValuesGroup $valuesGroup
     * @param integer     $level
     * @param integer     $groupIdx
     * @param boolean     $inGroup
     *
     * @throws FieldRequiredException
     */
    private function FieldValuesPairs(ValuesGroup $valuesGroup, $level = 0, $groupIdx = 0, $inGroup = false)
    {
        $groupCount = 0;
        $allFields = $this->fieldSet->all();

        while (null !== $this->lexer->lookahead) {
            switch ($this->lexer->lookahead['type']) {
                case Lexer::T_OPEN_PARENTHESIS:
                case Lexer::T_MULTIPLY:
                    $groupCount++;

                    $this->validateGroupsCount($groupIdx, $groupCount, $level);
                    $valuesGroup->addGroup($this->Group($level+1, $groupCount-1));
                    break;

                case Lexer::T_IDENTIFIER:
                    $fieldName = $this->getFieldName($this->FieldIdentification());
                    unset($allFields[$fieldName]);

                    if ($valuesGroup->hasField($fieldName)) {
                        $this->FieldValues($fieldName, $valuesGroup->getField($fieldName), $level, $groupIdx);
                    } else {
                        $valuesGroup->addField($fieldName, $this->FieldValues($fieldName, null, $level, $groupIdx));
                    }
                    break;

                case ($inGroup && Lexer::T_CLOSE_PARENTHESIS):
                    // Group closing is handled using the Group() method
                    break 2;

                default:
                    $this->syntaxError('"(" or FieldIdentification');
                    break;
            }
        }

        // Now run trough all the remaining fields and look if there are required
        foreach ($allFields as $fieldName => $filterConfig) {
            if ($filterConfig->isRequired()) {
                throw new FieldRequiredException($fieldName, $groupIdx, $level);
            }
        }
    }

    /**
     * FieldIdentification ::= String
     *
     * @return string
     */
    private function FieldIdentification()
    {
        $this->match(Lexer::T_IDENTIFIER);
        $identVariable = $this->lexer->token['value'];

        return $identVariable;
    }

    /**
     * FieldValues ::= [ "!" ] StringValue {"," [ "!" ] StringValue | [ "!" ] RangeValue | Comparison | PatternMatch}* [ ";" ]
     *
     * @param string    $fieldName
     * @param ValuesBag $valuesBag
     * @param integer   $level
     * @param integer   $groupIdx
     *
     * @return ValuesBag
     *
     * @throws ValuesOverflowException
     */
    private function FieldValues($fieldName, ValuesBag $valuesBag = null, $level = 0, $groupIdx = 0)
    {
        $valuesBag = $valuesBag ?: new ValuesBag();
        $hasValues = false;

        while (null !== $this->lexer->lookahead) {
            $valuesCount = $valuesBag->count();
            if ($valuesCount > $this->maxValues) {
                throw new ValuesOverflowException($fieldName, $this->maxValues, $valuesCount, $groupIdx, $level);
            }

            switch ($this->lexer->lookahead['type']) {
                case Lexer::T_STRING:
                case Lexer::T_FLOAT:
                case Lexer::T_INTEGER:
                    $peekToken = $this->lexer->glimpse();

                    if (Lexer::T_MINUS === $peekToken['type']) {
                        $this->assertAcceptsType('range', $fieldName);
                        $valuesBag->addRange($this->RangeValue());
                    } else {
                        $valuesBag->addSingleValue(new Value\SingleValue($this->StringValue()));
                    }

                    $hasValues = true;
                    break;

                case Lexer::T_OPEN_BRACE:
                case Lexer::T_CLOSE_BRACE:
                    $this->assertAcceptsType('range', $fieldName);
                    $valuesBag->addRange($this->RangeValue());

                    $hasValues = true;
                    break;

                case Lexer::T_NEGATE:
                    $this->match(Lexer::T_NEGATE);
                    $peekToken = $this->lexer->glimpse();

                    if ($this->lexer->isNextTokenAny(array(Lexer::T_OPEN_BRACE, Lexer::T_CLOSE_BRACE)) || (null !== $peekToken && Lexer::T_MINUS === $peekToken['type'])) {
                        $this->assertAcceptsType('range', $fieldName);
                        $valuesBag->addExcludedRange($this->RangeValue());
                    } else {
                        $valuesBag->addExcludedValue(new Value\SingleValue($this->StringValue()));
                    }

                    $hasValues = true;
                    break;

                case Lexer::T_LOWER_THAN:
                case Lexer::T_GREATER_THAN:
                    $this->assertAcceptsType('comparison', $fieldName);
                    $operator = $this->ComparisonOperator();
                    $valuesBag->addComparison(new Value\Compare($this->StringValue(), $operator));

                    $hasValues = true;
                    break;

                case Lexer::T_TILDE:
                    $this->assertAcceptsType('pattern-match', $fieldName);
                    $type = $this->PatternMatchOperator($caseInsenstive);
                    $valuesBag->addPatternMatch(new Value\PatternMatch($this->StringValue(), $type, $caseInsenstive));

                    $hasValues = true;
                    break;

                default:
                    $this->syntaxError('String | QuotedString | Range | Excluded Value | Excluded Range | Comparison | PatternMatch', $this->lexer->lookahead);
                    break;
            }

            if (null !== $this->lexer->lookahead) {
                if ($this->lexer->isNextToken(Lexer::T_COMMA)) {
                    $this->match(Lexer::T_COMMA);
                } elseif ($this->lexer->isNextToken(Lexer::T_SEMICOLON)) {
                    $this->match(Lexer::T_SEMICOLON);

                    // values list has ended.
                    break;
                } elseif ($this->lexer->isNextToken(Lexer::T_CLOSE_PARENTHESIS)) {
                    // Semicolon is optional when last
                    // values list has ended.
                    break;
                } else {
                    $this->syntaxError('; | , | )', $this->lexer->lookahead);
                }
            }
        }

        if (!$hasValues) {
            $this->syntaxError('String | QuotedString | Range | ExcludedValue | ExcludedRange | Comparison | PatternMatch', $this->lexer->lookahead);
        }

        return $valuesBag;
    }

    /**
     * StringValue ::= String | QuotedString | Float | Integer
     *
     * @return string
     */
    private function StringValue()
    {
        if (!$this->lexer->isNextTokenAny(array(Lexer::T_STRING, Lexer::T_FLOAT, Lexer::T_INTEGER))) {
            $this->syntaxError('simple string, quoted string, integer or float', $this->lexer->token);
        }

        $this->lexer->moveNext();
        $value = $this->lexer->token['value'];

        return $value;
    }

    /**
     * RangeValue ::= [ "[" | "]" ] StringValue "-" StringValue [ "[" | "]" ]
     *
     * @return Value\Range
     */
    private function RangeValue()
    {
        $lowerInclusive = true;
        $upperInclusive = true;

        if ($this->lexer->isNextTokenAny(array(Lexer::T_OPEN_BRACE, Lexer::T_CLOSE_BRACE))) {
            $lowerInclusive = $this->lexer->isNextToken(Lexer::T_OPEN_BRACE);
            $this->lexer->moveNext();
        }

        $lowerBound = $this->StringValue();
        $this->match(Lexer::T_MINUS);
        $upperBound = $this->StringValue();

        if ($this->lexer->isNextTokenAny(array(Lexer::T_OPEN_BRACE, Lexer::T_CLOSE_BRACE))) {
            $upperInclusive = $this->lexer->isNextToken(Lexer::T_CLOSE_BRACE);
            $this->lexer->moveNext();
        }

        return new Value\Range($lowerBound, $upperBound, $lowerInclusive, $upperInclusive);
    }

    /**
     * ComparisonOperator ::= "<" | "<=" | "<>" | ">" | ">="
     *
     * @return string
     */
    private function ComparisonOperator()
    {
        switch ($this->lexer->lookahead['value']) {
            case '<':
                $this->match(Lexer::T_LOWER_THAN);
                $operator = '<';

                if ($this->lexer->isNextToken(Lexer::T_EQUALS)) {
                    $this->match(Lexer::T_EQUALS);
                    $operator .= '=';
                } elseif ($this->lexer->isNextToken(Lexer::T_GREATER_THAN)) {
                    $this->match(Lexer::T_GREATER_THAN);
                    $operator .= '>';
                }

                return $operator;

            case '>':
                $this->match(Lexer::T_GREATER_THAN);
                $operator = '>';

                if ($this->lexer->isNextToken(Lexer::T_EQUALS)) {
                    $this->match(Lexer::T_EQUALS);
                    $operator .= '=';
                }

                return $operator;

            default:
                $this->syntaxError('<, <=, <>, >, >=');
        }
    }

    /**
     * PatternMatchOperator ::= ~* | ~> | ~< | ~? | ~!* | ~!> | ~!< | ~!? | ~i* | ~i> | ~i< | ~i? | ~i!* | ~i!> | ~i!< | ~i!?
     *
     * @param boolean $caseInsenstive Reference case insenstive state
     *
     * @return string
     */
    private function PatternMatchOperator(&$caseInsenstive)
    {
        $this->match(Lexer::T_TILDE);

        $caseInsenstive = false;

        // look for case insenstive
        if ($this->lexer->isNextToken(Lexer::T_STRING) && 'i' === strtolower($this->lexer->lookahead['value'])) {
            $caseInsenstive = true;

            $this->match(Lexer::T_STRING);
        }

        switch ($this->lexer->lookahead['value']) {
            case '*':
                $this->match(Lexer::T_MULTIPLY);
                $operator = 'CONTAINS';

                return $operator;

            case '>':
                $this->match(Lexer::T_GREATER_THAN);
                $operator = 'STARTS_WITH';

                return $operator;

            case '<':
                $this->match(Lexer::T_LOWER_THAN);
                $operator = 'ENDS_WITH';

                return $operator;

            case '?':
                $this->match(Lexer::T_QUESTION_MARK);
                $operator = 'REGEX';

                return $operator;

            case '!':
                $this->match(Lexer::T_NEGATE);

                $operator = 'NOT_';

                if ($this->lexer->isNextToken(Lexer::T_MULTIPLY)) {
                    $this->match(Lexer::T_MULTIPLY);
                    $operator .= 'CONTAINS';
                } elseif ($this->lexer->isNextToken(Lexer::T_GREATER_THAN)) {
                    $this->match(Lexer::T_GREATER_THAN);
                    $operator .= 'STARTS_WITH';
                } elseif ($this->lexer->isNextToken(Lexer::T_LOWER_THAN)) {
                    $this->match(Lexer::T_LOWER_THAN);
                    $operator .= 'ENDS_WITH';
                } elseif ($this->lexer->isNextToken(Lexer::T_QUESTION_MARK)) {
                    $this->match(Lexer::T_QUESTION_MARK);
                    $operator .= 'REGEX';
                } else {
                    $this->syntaxError('*, >, <, ?');
                }

                return $operator;

            default:
                $this->syntaxError('*, >, <, ?, !*, !>, !<, !?');
        }
    }
}
