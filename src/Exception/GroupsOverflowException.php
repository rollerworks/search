<?php

/**
 * This file is part of RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Exception;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class GroupsOverflowException extends \Exception implements ExceptionInterface
{
    protected $max;
    protected $count;
    protected $groupIdx;
    protected $nestingLevel;

    /**
     * @param integer $max
     * @param integer $count
     * @param integer $groupIdx
     * @param integer $nestingLevel
     */
    public function __construct($max, $count, $groupIdx, $nestingLevel)
    {
        $this->max = $max;
        $this->count = $count;
        $this->groupIdx = $groupIdx;
        $this->nestingLevel = $nestingLevel;

        parent::__construct(sprintf('Group "%d" at nesting level %d exceeds maximum number of groups, maximum: %d, total of groups: %d.', $groupIdx, $nestingLevel, $max, $count));
    }

    /**
     * @return integer
     */
    public function getMax()
    {
        return $this->max;
    }

    /**
     * @return integer
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * @return integer
     */
    public function getGroupIdx()
    {
        return $this->groupIdx;
    }

    /**
     * @return integer
     */
    public function getNestingLevel()
    {
        return $this->nestingLevel;
    }
}
