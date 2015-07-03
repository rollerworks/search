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
use Rollerworks\Component\Search\Exception\FieldRequiredException;

class SearchFieldRequiredExceptionParser implements ExceptionParserInterface
{
    public function accepts(\Exception $exception)
    {
        return $exception instanceof FieldRequiredException;
    }

    /**
     * @param \Exception|FieldRequiredException $exception
     *
     * @return array
     */
    public function parseException(\Exception $exception)
    {
        return [
            'message' => 'Field "{{ field }}" is required but is missing in group {{ group }} at nesting level {{ nesting }}.',
            'parameters' => [
                'field' => $exception->getFieldName(),
                'group' => $exception->getGroupIdx(),
                'nesting' => $exception->getNestingLevel(),
            ],
        ];
    }
}
