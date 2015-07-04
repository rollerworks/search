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
class GroupsNestingException extends InputProcessorException
{
    /**
     * @var string
     */
    private $max;

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
     * @param string $max
     * @param int    $groupIdx
     * @param int    $nestingLevel
     */
    public function __construct($max, $groupIdx, $nestingLevel)
    {
        $this->max = $max;
        $this->groupIdx = $groupIdx;
        $this->nestingLevel = $nestingLevel;

        parent::__construct(
            sprintf(
                'Group %d at nesting level %d exceeds maximum nesting level of %d.',
                $groupIdx,
                $nestingLevel,
                $max
            )
        );
    }

    /**
     * @return int
     */
    public function getMaxNesting()
    {
        return $this->max;
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
