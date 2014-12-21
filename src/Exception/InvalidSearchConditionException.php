<?php

/*
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Exception;

use Rollerworks\Component\Search\SearchConditionInterface;

final class InvalidSearchConditionException extends \Exception implements ExceptionInterface
{
    /**
     * @var SearchConditionInterface
     */
    private $condition;

    public function __construct(SearchConditionInterface $condition)
    {
        parent::__construct('SearchCondition contains one or more invalid values.');

        $this->condition = $condition;
    }

    /**
     * @return SearchConditionInterface
     */
    public function getCondition()
    {
        return $this->condition;
    }
}
