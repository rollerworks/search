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

namespace Rollerworks\Component\Search\Exception;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class StringLexerException extends InputProcessorException
{
    // StringQueryInput
    public const FIELD_REQUIRES_VALUES = 'A field must have at least one value';
    public const INCORRECT_VALUES_SEPARATOR = 'Values must be separated by a ",". A values list must end with ";" or ")"';
    public const CANNOT_CLOSE_UNOPENED_GROUP = 'Cannot close group as this field is not in a group';
    public const GROUP_LOGICAL_WITHOUT_GROUP = 'A group logical operator can only be used at the start of the input or before a group opening';

    // StringLexer
    public const UNKNOWN_PATTERN_MATCH_FLAG = 'Unknown operator flag, expected "i" and/or "!"';
    public const NO_SPACES_IN_OPERATOR = 'Spaces are not allowed within an operator';
    public const INCOMPLETE_VALUE_PATTERN = 'Missing operator and value pattern';
    public const SPACES_REQ_QUOTING = 'A value containing spaces must be surrounded by quotes';
    public const SPECIAL_CHARS_REQ_QUOTING = 'A value containing special characters must be surrounded by quotes';
    public const QUOTED_VALUE_REQUIRE_QUOTING = 'A value containing quotes must be surrounded by quotes';
    public const VALUE_QUOTES_MUST_ESCAPE = 'Quotes in a quoted value must be escaped';
    public const MISSING_END_QUOTE = 'Missing quote to end the value';

    /**
     * @param int            $column
     * @param int            $line
     * @param array|string[] $expected
     * @param string         $got
     *
     * @return StringLexerException
     */
    public static function syntaxError(int $column, int $line, $expected, string $got)
    {
        if ($expected) {
            $exp = new self(
                '',
                '[Syntax Error] line {{ line }} col {{ column }}: Expected {{ expected }}, got {{ got }}.',
                [
                    '{{ line }}' => $line,
                    '{{ column }}' => $column,
                    '{{ expected }}' => $expected,
                    '{{ got }}' => $got,
                ]
            );
        } else {
            $exp = new self(
                '',
                '[Syntax Error] line {{ line }} col {{ column }}: Unexpected {{ unexpected }}.',
                [
                    '{{ line }}' => $line,
                    '{{ column }}' => $column,
                    '{{ unexpected }}' => $got,
                ]
            );
        }

        $exp->setTranslatedParameters(['unexpected', 'got']);

        return $exp;
    }

    /**
     * @param int    $column
     * @param int    $line
     * @param string $message
     *
     * @return StringLexerException
     */
    public static function formatError(int $column, int $line, string $message)
    {
        $exp = new self(
            '',
            '[Format Error] line {{ line }} col {{ column }}: {{ message }}.',
            [
                '{{ line }}' => $line,
                '{{ column }}' => $column,
                '{{ message }}' => $message,
            ]
        );

        $exp->setTranslatedParameters(['message']);

        return $exp;
    }
}
