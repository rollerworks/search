<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Bundle\SearchBundle\ExceptionParser;

use Rollerworks\Component\ExceptionParser\ExceptionParserInterface;
use Rollerworks\Component\Search\Input\FilterQuery\QueryException;

class QueryExceptionParser implements ExceptionParserInterface
{
    public function accepts(\Exception $exception)
    {
        return $exception instanceof QueryException;
    }

    /**
     * @param QueryException|\Exception $exception
     *
     * @return array
     */
    public function parseException(\Exception $exception)
    {
        if ($exception->getExpected()) {
            return [
                'message' => '[Syntax Error] line {{ line }} col {{ column }}: Expected {{ expected }}, got "{{ got }}".',
                'parameters' => [
                    'line' => $exception->getSyntaxLine(),
                    'column' => $exception->getCol(),
                    'expected' => $exception->getExpected(),
                    'got' => $exception->getInstead(),
                ],
            ];
        } else {
            return [
                'message' => '[Syntax Error] line {{ line }} col {{ column }}: Unexpected {{ unexpected }}.',
                'parameters' => [
                    'line' => $exception->getSyntaxLine(),
                    'column' => $exception->getCol(),
                    'unexpected' => $exception->getInstead(),
                ],
            ];
        }
    }
}
