<?php

/*
 * This file is part of the RollerworksRecordFilterBundle package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Value;

use Rollerworks\Bundle\RecordFilterBundle\Value\Compare;
use Rollerworks\Bundle\RecordFilterBundle\Value\Range;
use Rollerworks\Bundle\RecordFilterBundle\Value\SingleValue;

/**
 * ValuesBag.
 *
 * Holds all the values (per type) of a filter
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * @api
 */
class FilterValuesBag
{
    /**
     * @var SingleValue[]
     */
    protected $singleValues = array();

    /**
     * @var SingleValue[]
     */
    protected $excludes = array();

    /**
     * @var Range[]
     */
    protected $ranges = array();

    /**
     * @var Range[]
     */
    protected $excludedRanges = array();

    /**
     * @var Compare[]
     */
    protected $compares = array();

    /**
     * @var string
     */
    protected $label;

    /**
     * @var string
     */
    protected $originalInput;

    /**
     * Constructor.
     *
     * @param string        $label
     * @param string        $originalInput
     * @param SingleValue[] $singleValues
     * @param SingleValue[] $excludes
     * @param Range[]       $ranges
     * @param Compare[]     $compares
     * @param Range[]       $excludedRanges
     *
     * @api
     */
    public function __construct($label, $originalInput = null, array $singleValues = array(), array $excludes = array(), array $ranges = array(), array $compares = array(), array $excludedRanges = array())
    {
        $this->singleValues   = $singleValues;
        $this->excludes       = $excludes;
        $this->ranges         = $ranges;
        $this->excludedRanges = $excludedRanges;
        $this->compares       = $compares;

        $this->label         = $label;
        $this->originalInput = $originalInput;
    }

    /**
     * Returns the 'original' field label.
     *
     * @return string|null
     *
     * @api
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Returns the 'original' field input.
     *
     * @return string|null
     *
     * @api
     */
    public function getOriginalInput()
    {
        return $this->originalInput;
    }

    /**
     * Returns whether the bag has range-values.
     *
     * @return boolean
     *
     * @api
     */
    public function hasRanges()
    {
        return count($this->ranges) > 0;
    }

    /**
     * Returns the range-values of the bag.
     *
     * @return Range[]|array
     *
     * @api
     */
    public function getRanges()
    {
        return $this->ranges;
    }

    /**
     * Returns whether the bag has excluded-range values.
     *
     * @return boolean
     *
     * @api
     */
    public function hasExcludedRanges()
    {
        return count($this->excludedRanges) > 0;
    }

    /**
     * Returns the excluded-ranges of the bag.
     *
     * @return Range[]|array
     *
     * @api
     */
    public function getExcludedRanges()
    {
        return $this->excludedRanges;
    }

    /**
     * Returns whether the bag has excluded-values.
     *
     * @return boolean
     *
     * @api
     */
    public function hasExcludes()
    {
        return count($this->excludes) > 0;
    }

    /**
     * Returns the excluded-values of the bag.
     *
     * @return SingleValue[]
     *
     * @api
     */
    public function getExcludes()
    {
        return $this->excludes;
    }

    /**
     * Returns whether the bag has comparison-values.
     *
     * @return boolean
     *
     * @api
     */
    public function hasCompares()
    {
        return count($this->compares) > 0;
    }

    /**
     * Returns the comparison-values of the bag.
     *
     * @return Compare[]
     *
     * @api
     */
    public function getCompares()
    {
        return $this->compares;
    }

    /**
     * Returns whether the bag has single-values.
     *
     * @return boolean
     *
     * @api
     */
    public function hasSingleValues()
    {
        return count($this->singleValues) > 0;
    }

    /**
     * Returns the single-values of the bag.
     *
     * @return SingleValue[]
     *
     * @api
     */
    public function getSingleValues()
    {
        return $this->singleValues;
    }

    /**
     * Removes a single-value from the bag.
     *
     * @param integer $index
     *
     * @return self
     *
     * @api
     */
    public function removeSingleValue($index)
    {
        if (isset($this->singleValues[$index])) {
            unset($this->singleValues[$index]);
        }

        return $this;
    }

    /**
     * Removes an exclude-value from the bag.
     *
     * @param integer $index
     *
     * @return self
     *
     * @api
     */
    public function removeExclude($index)
    {
        if (isset($this->excludes[$index])) {
            unset($this->excludes[$index]);
        }

        return $this;
    }

    /**
     * Removes a range-value from the bag.
     *
     * @param integer $index
     *
     * @return self
     *
     * @api
     */
    public function removeRange($index)
    {
        if (isset($this->ranges[$index])) {
            unset($this->ranges[$index]);
        }

        return $this;
    }

    /**
     * Removes an excluded-range value from the bag.
     *
     * @param integer $index
     *
     * @return self
     *
     * @api
     */
    public function removeExcludedRange($index)
    {
        if (isset($this->excludedRanges[$index])) {
            unset($this->excludedRanges[$index]);
        }

        return $this;
    }

    /**
     * Removes a comparison-value from the bag.
     *
     * @param integer $index
     *
     * @return self
     *
     * @api
     */
    public function removeCompare($index)
    {
        if (isset($this->compares[$index])) {
            unset($this->compares[$index]);
        }

        return $this;
    }

    /**
     * Adds a single-value to the bag.
     *
     * @param SingleValue $value
     *
     * @return self
     *
     * @api
     */
    public function addSingleValue(SingleValue $value)
    {
        $this->singleValues[] = $value;

        return $this;
    }

    /**
     * Adds an excluded-value to the bag.
     *
     * @param SingleValue $value
     *
     * @return self
     *
     * @api
     */
    public function addExclude(SingleValue $value)
    {
        $this->excludes[] = $value;

        return $this;
    }

    /**
     * Adds a range-value to the bag.
     *
     * @param Range $range
     *
     * @return self
     *
     * @api
     */
    public function addRange(Range $range)
    {
        $this->ranges[] = $range;

        return $this;
    }

    /**
     * Adds an excluded-range value to the bag.
     *
     * @param Range $range
     *
     * @return self
     *
     * @api
     */
    public function addExcludedRange(Range $range)
    {
        $this->excludedRanges[] = $range;

        return $this;
    }

    /**
     * Adds a comparison-value to the bag.
     *
     * @param Compare $compare
     *
     * @return self
     *
     * @api
     */
    public function addCompare(Compare $compare)
    {
        $this->compares[] = $compare;

        return $this;
    }
}
