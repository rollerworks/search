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

use Rollerworks\Component\Search\Exception\UnknownValueIndex;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * ValuesBag.
 *
 * The value bag holds all the values per-type.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class ValuesBag
{
    const PATTERN_CONTAINS = 1;
    const PATTERN_STARTS_WITH = 2;
    const PATTERN_ENDS_WITH = 3;
    const PATTERN_REGEX = 4;
    const PATTERN_NOT_CONTAINS = 5;
    const PATTERN_NOT_STARTS = 6;
    const PATTERN_NOT_END = 7;
    const PATTERN_NOT_REGEX = 8;

    protected $excludedValues;
    protected $ranges;
    protected $excludedRanges;
    protected $comparisons;
    protected $singleValues;
    protected $patternMatchers;

    /**
     * @var array
     */
    protected $violations;

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
        $this->violations = array();
    }

    /**
     * @return array
     */
    public function getSingleValues()
    {
        return $this->singleValues;
    }

    public function addSingleValue($value)
    {
        $this->singleValues[] = $value;
    }

    public function replaceSingleValue($index, $value)
    {
        if (!isset($this->singleValues[$index])) {
            throw new UnknownValueIndex(sprintf('There is no single-value at index "%s"', $index));
        }

        $this->singleValues[$index] = $value;
    }

    public function hasSingleValues()
    {
        return !empty($this->singleValues);
    }

    public function removeSingleValue($index)
    {
        if (isset($this->singleValues[$index])) {
            unset($this->singleValues[$index]);
        }

        return $this;
    }

    public function addExcludedValue($value)
    {
        $this->excludedValues[] = $value;

        return $this;
    }

    public function replaceExcludedValue($index, $value)
    {
        if (!isset($this->excludedValues[$index])) {
            throw new UnknownValueIndex(sprintf('There is no excluded value at index "%s"', $index));
        }

        $this->excludedValues[$index] = $value;
    }

    public function hasExcludedValues()
    {
        return !empty($this->excludedValues);
    }

    public function getExcludedValues()
    {
        return $this->excludedValues;
    }

    public function removeExcludedValue($index)
    {
        if (isset($this->excludedValues[$index])) {
            unset($this->excludedValues[$index]);
        }

        return $this;
    }

    public function addRange($lower, $upper, $inclusiveLower = true, $inclusiveUpper = true)
    {
        $this->ranges[] = array(
            'lower' => $lower, 'upper' => $upper, 'lower_inclusive' => $inclusiveLower,
            'upper_inclusive' => $inclusiveUpper
        );

        return $this;
    }

    public function replaceRange($index, $lower, $upper, $inclusiveLower = true, $inclusiveUpper = true)
    {
        if (!isset($this->ranges[$index])) {
            throw new UnknownValueIndex(sprintf('There is no range value at index "%s"', $index));
        }

        $this->ranges[$index] = array(
            'lower' => $lower, 'upper' => $upper, 'lower_inclusive' => $inclusiveLower,
            'upper_inclusive' => $inclusiveUpper
        );
    }

    public function hasRanges()
    {
        return !empty($this->ranges);
    }

    public function getRanges()
    {
        return $this->ranges;
    }

    public function removeRange($index)
    {
        if (isset($this->ranges[$index])) {
            unset($this->ranges[$index]);
        }

        return $this;
    }

    public function addExcludedRange($lower, $upper, $inclusiveLower = true, $inclusiveUpper = true)
    {
        $this->excludedRanges[] = array(
            'lower' => $lower, 'upper' => $upper, 'lower_inclusive' => $inclusiveLower,
            'upper_inclusive' => $inclusiveUpper
        );

        return $this;
    }

    public function replaceExcludedRange($index, $lower, $upper, $inclusiveLower = true, $inclusiveUpper = true)
    {
        if (!isset($this->excludedRanges[$index])) {
            throw new UnknownValueIndex(sprintf('There is no excluded-range value at index "%s"', $index));
        }

        $this->excludedRanges[$index] = array(
            'lower' => $lower, 'upper' => $upper, 'lower_inclusive' => $inclusiveLower,
            'upper_inclusive' => $inclusiveUpper
        );
    }

    public function hasExcludedRanges()
    {
        return !empty($this->excludedRanges);
    }

    public function getExcludedRanges()
    {
       return $this->excludedRanges;
    }

    public function removeExcludedRange($index)
    {
        if (isset($this->excludedRanges[$index])) {
            unset($this->excludedRanges[$index]);
        }

        return $this;
    }

    public function addComparison($value, $operator)
    {
        if (!in_array($operator, array('>=', '<=', '<>', '<', '>'))) {
            throw new \InvalidArgumentException(sprintf('Unknown operator "%s", supported operators are: ">=", "<=", "<>", "<", ">"', $operator));
        }

        $this->comparisons[] = array('value' => $value, 'operator' => $operator);
    }

    public function replaceComparison($index, $value, $operator)
    {
        if (!isset($this->comparisons[$index])) {
            throw new UnknownValueIndex(sprintf('There is no comparison value at index "%s"', $index));
        }

        if (!in_array($operator, array('>=', '<=', '<>', '<', '>'))) {
            throw new \InvalidArgumentException(sprintf('Unknown operator "%s", supported operators are: ">=", "<=", "<>", "<", ">"', $operator));
        }

        $this->comparisons[$index] = array('value' => $value, 'operator' => $operator);
    }

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
        }

        return $this;
    }

    public function getPatternMatch()
    {
        return $this->patternMatchers;
    }

    public function addPatternMatch($value, $patternType)
    {
        if ($patternType < 1 || $patternType > 8) {
            throw new \InvalidArgumentException('Unknown pattern-match type.');
        }

        $this->patternMatchers[] = array('value' => $value, 'type' => $patternType);
    }

    public function replacePatternMatch($index, $value, $patternType)
    {
        if ($patternType < 1 || $patternType > 8) {
            throw new \InvalidArgumentException('Unknown pattern-match type.');
        }

        if (!isset($this->patternMatchers[$index])) {
            throw new UnknownValueIndex(sprintf('There is no pattern-match at index "%s"', $index));
        }

        $this->patternMatchers[$index] = array('value' => $value, 'type' => $patternType);
    }

    public function hasPatternMatch()
    {
        return !empty($this->patternMatchers);
    }

    public function removePatternMatch($index)
    {
        if (isset($this->patternMatchers[$index])) {
            unset($this->patternMatchers[$index]);
        }

        return $this;
    }

    /**
     * Set the violations for the values-bag.
     *
     * @param ConstraintViolation[] $violations
     *
     * @return self
     */
    public function setViolations(array $violations)
    {
        $this->violations = $violations;

        return $this;
    }

    /**
     * @return boolean
     */
    public function hasViolations()
    {
        return !empty($this->violations);
    }

    /**
     * @return array
     */
    public function getViolations()
    {
        return $this->violations;
    }
}
