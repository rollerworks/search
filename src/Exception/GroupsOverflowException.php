<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Exception;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class GroupsOverflowException extends InputProcessorException
{
    /**
     * @var int
     */
    private $max;

    /**
     * @var int
     */
    private $count;

    /**
     * @var int
     */
    private $groupIdx;

    /**
     * @var int
     */
    private $nestingLevel;

    /**
     * Constructor.
     *
     * @param int $max
     * @param int $count
     * @param int $groupIdx
     * @param int $nestingLevel
     */
    public function __construct($max, $count, $groupIdx, $nestingLevel)
    {
        $this->max = $max;
        $this->count = $count;
        $this->groupIdx = $groupIdx;
        $this->nestingLevel = $nestingLevel;

        parent::__construct(
            sprintf(
                'Group "%d" at nesting level %d exceeds maximum number of groups, maximum: %d, total of groups: %d.',
                $groupIdx,
                $nestingLevel,
                $max,
                $count
            )
        );
    }

    /**
     * @return int
     */
    public function getMax()
    {
        return $this->max;
    }

    /**
     * @return int
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * @return int
     */
    public function getGroupIdx()
    {
        return $this->groupIdx;
    }

    /**
     * @return int
     */
    public function getNestingLevel()
    {
        return $this->nestingLevel;
    }
}
