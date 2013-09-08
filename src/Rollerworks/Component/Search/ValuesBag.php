<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search;

use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\SingleValue;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * ValuesBag holds all the values per-type.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class ValuesBag implements \Countable
{
    protected $excludedValues;
    protected $ranges;
    protected $excludedRanges;
    protected $comparisons;
    protected $singleValues;
    protected $patternMatchers;

    protected $valuesCount;

    /**
     * @var array
     */
    protected $errors;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->singleValues = array();
        $this->excludedValues = array();
        $this->ranges = array();
        $this->excludedRanges = array();
        $this->comparisons = array();
        $this->patternMatchers = array();
        $this->errors = array();
        $this->valuesCount = 0;
    }

    /**
     * @return SingleValue[]
     */
    public function getSingleValues()
    {
        return $this->singleValues;
    }

    public function addSingleValue(SingleValue $value)
    {
        $this->singleValues[] = $value;
        $this->valuesCount++;

        return $this;
    }

    public function hasSingleValues()
    {
        return !empty($this->singleValues);
    }

    public function removeSingleValue($index)
    {
        if (isset($this->singleValues[$index])) {
            unset($this->singleValues[$index]);

            $this->valuesCount--;
        }

        return $this;
    }

    public function addExcludedValue(SingleValue $value)
    {
        $this->excludedValues[] = $value;
        $this->valuesCount++;

        return $this;
    }

    public function hasExcludedValues()
    {
        return !empty($this->excludedValues);
    }

    /**
     * @return SingleValue[]
     */
    public function getExcludedValues()
    {
        return $this->excludedValues;
    }

    public function removeExcludedValue($index)
    {
        if (isset($this->excludedValues[$index])) {
            unset($this->excludedValues[$index]);

            $this->valuesCount--;
        }

        return $this;
    }

    public function addRange(Range $range)
    {
        $this->ranges[] = $range;
        $this->valuesCount++;

        return $this;
    }

    public function hasRanges()
    {
        return count($this->ranges) > 0;
    }

    /**
     * @return Range[]
     */
    public function getRanges()
    {
        return $this->ranges;
    }

    public function removeRange($index)
    {
        if (isset($this->ranges[$index])) {
            unset($this->ranges[$index]);

            $this->valuesCount--;
        }

        return $this;
    }

    public function addExcludedRange(Range $range)
    {
        $this->excludedRanges[] = $range;
        $this->valuesCount++;

        return $this;
    }

    public function hasExcludedRanges()
    {
        return !empty($this->excludedRanges);
    }

    /**
     * @return Range[]
     */
    public function getExcludedRanges()
    {
       return $this->excludedRanges;
    }

    public function removeExcludedRange($index)
    {
        if (isset($this->excludedRanges[$index])) {
            unset($this->excludedRanges[$index]);

            $this->valuesCount--;
        }

        return $this;
    }

    public function addComparison(Compare $value)
    {
        $this->comparisons[] = $value;
        $this->valuesCount++;

        return $this;
    }

    /**
     * @return Compare[]
     */
    public function getComparisons()
    {
        return $this->comparisons;
    }

    public function hasComparisons()
    {
        return !empty($this->comparisons);
    }

    public function removeComparison($index)
    {
        if (isset($this->comparisons[$index])) {
            unset($this->comparisons[$index]);

            $this->valuesCount--;
        }

        return $this;
    }

    /**
     * @return PatternMatch[]
     */
    public function getPatternMatchers()
    {
        return $this->patternMatchers;
    }

    public function addPatternMatch(PatternMatch $value)
    {
        $this->patternMatchers[] = $value;
        $this->valuesCount++;

        return $this;
    }

    public function hasPatternMatchers()
    {
        return !empty($this->patternMatchers);
    }

    public function removePatternMatch($index)
    {
        if (isset($this->patternMatchers[$index])) {
            unset($this->patternMatchers[$index]);

            $this->valuesCount--;
        }

        return $this;
    }

    public function addError(ValuesError $error)
    {
        $this->errors[] = $error;

        return $this;
    }

    /**
     * @return boolean
     */
    public function hasErrors()
    {
        return !empty($this->errors);
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @return integer
     */
    public function count()
    {
        return $this->valuesCount;
    }}
