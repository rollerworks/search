<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search\Exception;

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
