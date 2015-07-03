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
use Rollerworks\Component\Search\Exception\UnsupportedValueTypeException;

class SearchUnsupportedValueTypeExceptionParser implements ExceptionParserInterface
{
    public function accepts(\Exception $exception)
    {
        return $exception instanceof UnsupportedValueTypeException;
    }

    /**
     * @param \Exception|UnsupportedValueTypeException $exception
     *
     * @return array
     */
    public function parseException(\Exception $exception)
    {
        return [
            'message' => 'Field "{{ field }}" does not accept '.$exception->getValueType().' values.',
            'parameters' => [
                'field' => $exception->getFieldName(),
            ],
        ];
    }
}
