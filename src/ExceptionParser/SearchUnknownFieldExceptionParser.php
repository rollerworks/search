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
use Rollerworks\Component\Search\Exception\UnknownFieldException;

class SearchUnknownFieldExceptionParser implements ExceptionParserInterface
{
    public function accepts(\Exception $exception)
    {
        return $exception instanceof UnknownFieldException;
    }

    /**
     * @param \Exception|UnknownFieldException $exception
     *
     * @return array
     */
    public function parseException(\Exception $exception)
    {
        return [
            'message' => 'Field "{{ field }}" is not registered in the FieldSet or available as alias.',
            'parameters' => [
                'field' => $exception->getFieldName(),
            ],
        ];
    }
}
