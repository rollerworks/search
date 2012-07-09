<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
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
 * Holds all the values (per type) of an filter
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
     * Returns whether the bag has Ranges.
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
     * Returns the Ranges of the bag.
     *
     * @return Range[]
     *
     * @api
     */
    public function getRanges()
    {
        return $this->ranges;
    }

    /**
     * Returns whether the bag has Excluded Ranges.
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
     * Returns the Excluded Ranges of the bag.
     *
     * @return Range[]
     *
     * @api
     */
    public function getExcludedRanges()
    {
        return $this->excludedRanges;
    }

    /**
     * Returns whether the bag has Excludes.
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
     * Returns the Excludes of the bag.
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
     * Returns whether the bag has Compares.
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
     * Returns the Compares of the bag.
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
     * Returns whether the bag has SingleValues.
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
     * Removes the Values of the bag.
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
     * @param integer $piIndex
     *
     * @return self
     *
     * @api
     */
    public function removeSingleValue($piIndex)
    {
        if (isset($this->singleValues[$piIndex])) {
            unset($this->singleValues[$piIndex]);
        }

        return $this;
    }

    /**
     * Removes a Exclude from the bag.
     *
     * @param integer $piIndex
     *
     * @return self
     *
     * @api
     */
    public function removeExclude($piIndex)
    {
        if (isset($this->excludes[$piIndex])) {
            unset($this->excludes[$piIndex]);
        }

        return $this;
    }

    /**
     * Removes a Range from the bag.
     *
     * @param integer $piIndex
     *
     * @return self
     *
     * @api
     */
    public function removeRange($piIndex)
    {
        if (isset($this->ranges[$piIndex])) {
            unset($this->ranges[$piIndex]);
        }

        return $this;
    }

    /**
     * Removes an Excluded Range from the bag.
     *
     * @param integer $piIndex
     *
     * @return self
     *
     * @api
     */
    public function removeExcludedRange($piIndex)
    {
        if (isset($this->excludedRanges[$piIndex])) {
            unset($this->excludedRanges[$piIndex]);
        }

        return $this;
    }

    /**
     * Removes a Compare from the bag.
     *
     * @param integer $piIndex
     *
     * @return self
     *
     * @api
     */
    public function removeCompare($piIndex)
    {
        if (isset($this->compares[$piIndex])) {
            unset($this->compares[$piIndex]);
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
     * Add a Exclude to the bag.
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
     * Add a range to the bag.
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
     * Adds an Excluded Range to the bag.
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
     * Adds a Compare to the bag.
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
