<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\RecordFilterBundle\Value;

use Rollerworks\RecordFilterBundle\Value\Compare;
use Rollerworks\RecordFilterBundle\Value\Range;
use Rollerworks\RecordFilterBundle\Value\SingleValue;
use \InvalidArgumentException;

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
     * 'Original' field label
     *
     * @var string
     */
    protected $label;

    /**
     * The last-value index
     *
     * @var integer
     */
    protected $lastValIndex;

    /**
     * 'Original' field input
     *
     * @var string
     */
    protected $originalInput;

    /**
     * Constructor.
     *
     * @param string                                                $label
     * @param string                                                $originalInput
     * @param \Rollerworks\RecordFilterBundle\Value\SingleValue[]   $singleValues
     * @param \Rollerworks\RecordFilterBundle\Value\SingleValue[]   $excludes
     * @param \Rollerworks\RecordFilterBundle\Value\Range[]         $ranges
     * @param \Rollerworks\RecordFilterBundle\Value\Compare[]       $compares
     * @param \Rollerworks\RecordFilterBundle\Value\Range[]         $excludedRanges
     * @param integer                                               $lastValIndex
     *
     * @api
     */
    public function __construct($label, $originalInput = null, array $singleValues = array(), array $excludes = array(), array $ranges = array(), array $compares = array(), array $excludedRanges = array(), $lastValIndex = -1)
    {
        $this->singleValues   = $singleValues;
        $this->excludes       = $excludes;
        $this->ranges         = $ranges;
        $this->excludedRanges = $excludedRanges;
        $this->compares       = $compares;

        $this->label         = $label;
        $this->originalInput = $originalInput;
        $this->lastValIndex  = $lastValIndex;
    }

    /**
     * Get the 'original' field label
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
     * Set the last value index.
     *
     * This value can not be lower then the current-value.
     *
     * @param integer $index
     *
     * @api
     */
    public function setLastValueIndex($index)
    {
        if ($index < $this->lastValIndex) {
            throw new \InvalidArgumentException(sprintf('New value "%s" index may not be lower then the current "%s".', $index, $this->lastValIndex));
        }

        $this->lastValIndex = $index;
    }

    /**
     * Get the last value index
     *
     * @return string|null
     *
     * @api
     */
    public function getLastValueIndex()
    {
        return $this->lastValIndex;
    }

    /**
     * Get the 'original' field input
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
     * Returns whether the filter has Ranges
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
     * Returns the Ranges of the filter
     *
     * @return \Rollerworks\RecordFilterBundle\Value\Range[]
     *
     * @api
     */
    public function getRanges()
    {
        return $this->ranges;
    }

    /**
     * Returns whether the filter has Excluded Ranges
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
     * Returns the Excluded Ranges of the filter
     *
     * @return \Rollerworks\RecordFilterBundle\Value\Range[]
     *
     * @api
     */
    public function getExcludedRanges()
    {
        return $this->excludedRanges;
    }

    /**
     * Returns whether the filter has Excludes
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
     * Returns the Excludes of the filter
     *
     * @return \Rollerworks\RecordFilterBundle\Value\SingleValue[]
     *
     * @api
     */
    public function getExcludes()
    {
        return $this->excludes;
    }

    /**
     * Returns whether the filter has Compares
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
     * Returns the Compares of the filter
     *
     * @return \Rollerworks\RecordFilterBundle\Value\Compare[]
     *
     * @api
     */
    public function getCompares()
    {
        return $this->compares;
    }

    /**
     * Returns whether the filter has Values
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
     * Removes the Values of the filter
     *
     * @return \Rollerworks\RecordFilterBundle\Value\SingleValue[]
     *
     * @api
     */
    public function getSingleValues()
    {
        return $this->singleValues;
    }

    /**
     * Removes a single-value from the filter
     *
     * @param integer $piIndex
     * @return \Rollerworks\RecordFilterBundle\ValuesBag
     *
     * @api
     */
    function removeSingleValue($piIndex)
    {
        if (isset($this->singleValues[$piIndex])) {
            unset($this->singleValues[$piIndex]);
        }

        return $this;
    }

    /**
     * Removes a Exclude from the filter
     *
     * @param integer $piIndex
     * @return \Rollerworks\RecordFilterBundle\ValuesBag
     *
     * @api
     */
    function removeExclude($piIndex)
    {
        if (isset($this->excludes[$piIndex])) {
            unset($this->excludes[$piIndex]);
        }

        return $this;
    }

    /**
     * Removes a Range from the filter
     *
     * @param integer $piIndex
     * @return \Rollerworks\RecordFilterBundle\ValuesBag
     *
     * @api
     */
    function removeRange($piIndex)
    {
        if (isset($this->ranges[$piIndex])) {
            unset($this->ranges[$piIndex]);
        }

        return $this;
    }

    /**
     * Removes an Excluded Range from the filter
     *
     * @param integer $piIndex
     * @return \Rollerworks\RecordFilterBundle\ValuesBag
     *
     * @api
     */
    function removeExcludedRange($piIndex)
    {
        if (isset($this->excludedRanges[$piIndex])) {
            unset($this->excludedRanges[$piIndex]);
        }

        return $this;
    }

    /**
     * Removes a Compare from the filter
     *
     * @param integer $piIndex
     * @return \Rollerworks\RecordFilterBundle\ValuesBag
     *
     * @api
     */
    function removeCompare($piIndex)
    {
        if (isset($this->compares[$piIndex])) {
            unset($this->compares[$piIndex]);
        }

        return $this;
    }

    /**
     * Add a single-value to the filter
     *
     * @param SingleValue $value
     * @return \Rollerworks\RecordFilterBundle\ValuesBag
     *
     * @api
     */
    function addSingleValue(SingleValue $value)
    {
        $this->singleValues[++$this->lastValIndex] = $value;

        return $this;
    }

    /**
     * Add a Exclude to the filter
     *
     * @param SingleValue $value
     * @return \Rollerworks\RecordFilterBundle\ValuesBag
     *
     * @api
     */
    function addExclude(SingleValue $value)
    {
        $this->excludes[++$this->lastValIndex] = $value;

        return $this;
    }

    /**
     * Add a range to the filter
     *
     * @param Range $range
     * @return \Rollerworks\RecordFilterBundle\ValuesBag
     *
     * @api
     */
    function addRange(Range $range)
    {
        $this->ranges[++$this->lastValIndex] = $range;

        return $this;
    }

    /**
     * Add an Excluded Range to the filter
     *
     * @param Range $range
     * @return \Rollerworks\RecordFilterBundle\ValuesBag
     *
     * @api
     */
    function addExcludedRange(Range $range)
    {
        $this->excludedRanges[++$this->lastValIndex] = $range;

        return $this;
    }

    /**
     * Add a Compare to the filter
     *
     * @param Compare $compare
     * @return \Rollerworks\RecordFilterBundle\ValuesBag
     *
     * @api
     */
    function addCompare(Compare $compare)
    {
        $this->compares[++$this->lastValIndex] = $compare;

        return $this;
    }
}