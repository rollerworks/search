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

namespace Rollerworks\Component\Search\Input\StringQuery;

use Rollerworks\Component\Search\Exception\InputProcessorException;

final class QueryException extends InputProcessorException
{
    /**
     * @param int            $column
     * @param int            $line
     * @param array|string[] $expected
     * @param string         $got
     *
     * @return QueryException
     */
    public static function syntaxError(int $column, int $line, $expected, string $got)
    {
        if ($expected) {
            $exp = new self(
                '',
                '[Syntax Error] line {{ line }} col {{ column }}: Expected {{ expected }}, got "{{ got }}".',
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
}
