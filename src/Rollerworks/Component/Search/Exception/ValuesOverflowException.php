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
            'Field "%s" in group %d at nesting level %d exceeds maximum number values in per group, maximum: %d, total of values: %d.',
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
