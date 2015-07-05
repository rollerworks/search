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
use Rollerworks\Component\Search\Exception\ValuesOverflowException;

class SearchValuesOverflowExceptionParser implements ExceptionParserInterface
{
    public function accepts(\Exception $exception)
    {
        return $exception instanceof ValuesOverflowException;
    }

    /**
     * @param \Exception|ValuesOverflowException $exception
     *
     * @return array
     */
    public function parseException(\Exception $exception)
    {
        return [
            'message' => 'Field {{ field }} in group {{ group }} at nesting level {{ nesting }} exceeds the maximum number values per group, maximum: {{ max }}, total of values: {{ count }}.',
            'parameters' => [
                'field' => $exception->getFieldName(),
                'group' => $exception->getGroupIdx(),
                'nesting' => $exception->getNestingLevel(),
                'max' => $exception->getMax(),
                'count' => $exception->getCount(),
            ],
        ];
    }
}
