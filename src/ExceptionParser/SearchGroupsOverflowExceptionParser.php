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
use Rollerworks\Component\Search\Exception\GroupsOverflowException;

class SearchGroupsOverflowExceptionParser implements ExceptionParserInterface
{
    public function accepts(\Exception $exception)
    {
        return $exception instanceof GroupsOverflowException;
    }

    /**
     * @param \Exception|GroupsOverflowException $exception
     *
     * @return array
     */
    public function parseException(\Exception $exception)
    {
        return [
            'message' => 'Group {{ group }} at nesting level %d exceeds maximum number of groups, maximum: %d, total of groups: %d.',
            'parameters' => [
                'group' => $exception->getGroupIdx(),
                'nesting' => $exception->getNestingLevel(),
                'max' => $exception->getMax(),
                'count' => $exception->getCount(),
            ],
        ];
    }
}
