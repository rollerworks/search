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

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class GroupsNestingException extends \Exception implements ExceptionInterface
{
    protected $max;
    protected $groupIdx;
    protected $nestingLevel;

    /**
     * @param string  $max
     * @param integer $groupIdx
     * @param integer $nestingLevel
     */
    public function __construct($max, $groupIdx, $nestingLevel)
    {
        $this->max = $max;
        $this->groupIdx = $groupIdx;
        $this->nestingLevel = $nestingLevel;

        parent::__construct(sprintf('Group %d at nesting level %d exceeds maximum nesting level of %d', $groupIdx, $nestingLevel, $max));
    }

    /**
     * @return integer
     */
    public function getMaxNesting()
    {
        return $this->max;
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
