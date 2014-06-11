<?php

/**
 * This file is part of the RollerworksSearch Component package.
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
class ValuesOverflowException extends \Exception implements ExceptionInterface
{
    protected $fieldName;
    protected $max;
    protected $count;
    protected $groupIdx;
    protected $nestingLevel;

    /**
     * @param string  $fieldName
     * @param integer $max
     * @param integer $count
     * @param integer $groupIdx
     * @param integer $nestingLevel
     */
    public function __construct($fieldName, $max, $count, $groupIdx, $nestingLevel)
    {
        $this->max = $max;
        $this->count = $count;
        $this->groupIdx = $groupIdx;
        $this->nestingLevel = $nestingLevel;

        parent::__construct(sprintf(
            'Field "%s" in group %d at nesting level %d exceeds the maximum number of values per group, maximum: %d, total of values: %d.',
            $fieldName,
            $groupIdx,
            $nestingLevel,
            $max,
            $count
        ));
    }

    /**
     * @return string
     */
    public function getFieldName()
    {
        return $this->fieldName;
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
