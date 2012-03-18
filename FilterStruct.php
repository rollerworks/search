<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Rollerscapes
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link    http://projects.rollerscapes.net/RollerFramework
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */

namespace Rollerworks\RecordFilterBundle;

use Rollerworks\RecordFilterBundle\Struct\Compare;
use Rollerworks\RecordFilterBundle\Struct\Range;
use Rollerworks\RecordFilterBundle\Struct\Value;

use \InvalidArgumentException;

/**
 * FilterStructure class.
 *
 * Holds all the filtering information for field
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * @api
 */
class FilterStruct
{
    /**
     * @var \Rollerworks\RecordFilterBundle\Struct\Value[]
     */
    protected $singleValues = array();

    /**
     * @var \Rollerworks\RecordFilterBundle\Struct\Value[]
     */
    protected $excludes = array();

    /**
     * @var \Rollerworks\RecordFilterBundle\Struct\Range[]
     */
    protected $ranges = array();

    /**
     * @var \Rollerworks\RecordFilterBundle\Struct\Range[]
     */
    protected $excludedRanges = array();

    /**
     * @var \Rollerworks\RecordFilterBundle\Struct\Compare[]
     */
    protected $compares = array();

    /**
     * 'Original' field label
     *
     * @var string
     */
    protected $label;

    /**
     * 'Original' field input
     *
     * @var string
     */
    protected $originalInput;

    /**
     * Constructor.
     *
     * @param string                                                     $label
     * @param string                                                     $originalInput
     * @param \Rollerworks\RecordFilterBundle\Struct\Value[]   $singleValues
     * @param \Rollerworks\RecordFilterBundle\Struct\Value[]   $excludes
     * @param \Rollerworks\RecordFilterBundle\Struct\Range[]   $ranges
     * @param \Rollerworks\RecordFilterBundle\Struct\Compare[] $compares
     * @param \Rollerworks\RecordFilterBundle\Struct\Range[]   $excludedRanges
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
     * @return \Rollerworks\RecordFilterBundle\Struct\Range[]
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
     * @return \Rollerworks\RecordFilterBundle\Struct\Range[]
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
     * @return \Rollerworks\RecordFilterBundle\Struct\Value[]
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
     * @return \Rollerworks\RecordFilterBundle\Struct\Compare[]
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
     * @return \Rollerworks\RecordFilterBundle\Struct\Value[]
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
     * @return \Rollerworks\RecordFilterBundle\FilterStruct
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
     * @return \Rollerworks\RecordFilterBundle\FilterStruct
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
     * @return \Rollerworks\RecordFilterBundle\FilterStruct
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
     * @return \Rollerworks\RecordFilterBundle\FilterStruct
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
     * @return \Rollerworks\RecordFilterBundle\FilterStruct
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
}