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
use Rollerworks\Component\Search\Exception\GroupsNestingException;

class SearchGroupsNestingExceptionParser implements ExceptionParserInterface
{
    public function accepts(\Exception $exception)
    {
        return $exception instanceof GroupsNestingException;
    }

    /**
     * @param \Exception|GroupsNestingException $exception
     *
     * @return array
     */
    public function parseException(\Exception $exception)
    {
        return [
            'message' => 'Group {{ group }} at nesting level {{ nesting }} exceeds maximum nesting level of {{ max }}.',
            'parameters' => [
                'group' => $exception->getGroupIdx(),
                'nesting' => $exception->getNestingLevel(),
                'max' => $exception->getMaxNesting(),
            ],
        ];
    }
}
