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
class ValuesOverflowException extends InputProcessorException
{
    /**
     * @var string
     */
    protected $fieldName;

    /**
     * @var int
     */
    protected $max;

    /**
     * @var int
     */
    protected $count;

    /**
     * @var int
     */
    protected $groupIdx;

    /**
     * @var int
     */
    protected $nestingLevel;

    /**
     * Constructor.
     *
     * @param string $fieldName
     * @param int    $max
     * @param int    $groupIdx
     * @param int    $nestingLevel
     */
    public function __construct($fieldName, $max, $groupIdx, $nestingLevel)
    {
        $this->fieldName = $fieldName;
        $this->max = $max;
        $this->groupIdx = $groupIdx;
        $this->nestingLevel = $nestingLevel;

        parent::__construct(
            sprintf(
                'Field "%s" in group %d at nesting level %d exceeds the maximum number of values %d per group.',
                $fieldName,
                $groupIdx,
                $nestingLevel,
                $max
            )
        );
    }

    /**
     * @return string
     */
    public function getFieldName()
    {
        return $this->fieldName;
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
     *
     * @deprecated since beta5 to be removed in 2.0
     */
    public function getCount()
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
