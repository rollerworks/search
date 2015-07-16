<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search;

use Rollerworks\Component\Search\Exception\BadMethodCallException;
use Rollerworks\Component\Search\Exception\ValuesStructureIsLocked;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\SingleValue;

/**
 * A ValuesBag holds all the values per type.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class ValuesBag implements \Countable, \Serializable
{
    const VALUE_TYPE_RANGE = 'range';
    const VALUE_TYPE_COMPARISON = 'comparison';
    const VALUE_TYPE_PATTERN_MATCH = 'pattern-match';

    private $excludedValues = [];
    private $ranges = [];
    private $excludedRanges = [];
    private $comparisons = [];
    private $singleValues = [];
    private $patternMatchers = [];
    private $valuesCount = 0;
    private $errors = [];
    private $locked = false;

    /**
     * @return SingleValue[]
     */
    public function getSingleValues()
    {
        return $this->singleValues;
    }

    /**
     * @param SingleValue $value
     *
     * @return static
     */
    public function addSingleValue(SingleValue $value)
    {
        if ($this->locked) {
            throw new ValuesStructureIsLocked();
        }

        $this->singleValues[] = $value;
        ++$this->valuesCount;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasSingleValues()
    {
        return count($this->singleValues) > 0;
    }

    /**
     * @param int $index
     *
     * @return static
     */
    public function removeSingleValue($index)
    {
        if ($this->locked) {
            throw new ValuesStructureIsLocked();
        }

        if (isset($this->singleValues[$index])) {
            unset($this->singleValues[$index]);

            --$this->valuesCount;
        }

        return $this;
    }

    /**
     * @param SingleValue $value
     *
     * @return static
     */
    public function addExcludedValue(SingleValue $value)
    {
        if ($this->locked) {
            throw new ValuesStructureIsLocked();
        }

        $this->excludedValues[] = $value;
        ++$this->valuesCount;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasExcludedValues()
    {
        return count($this->excludedValues) > 0;
    }

    /**
     * @return SingleValue[]
     */
    public function getExcludedValues()
    {
        return $this->excludedValues;
    }

    /**
     * @param int $index
     *
     * @return static
     */
    public function removeExcludedValue($index)
    {
        if ($this->locked) {
            throw new ValuesStructureIsLocked();
        }

        if (isset($this->excludedValues[$index])) {
            unset($this->excludedValues[$index]);

            --$this->valuesCount;
        }

        return $this;
    }

    /**
     * @param Range $range
     *
     * @return static
     */
    public function addRange(Range $range)
    {
        if ($this->locked) {
            throw new ValuesStructureIsLocked();
        }

        $this->ranges[] = $range;
        ++$this->valuesCount;

        return $this;
    }

    /**
     * @return bool
     */
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

    /**
     * @param int $index
     *
     * @return static
     */
    public function removeRange($index)
    {
        if ($this->locked) {
            throw new ValuesStructureIsLocked();
        }

        if (isset($this->ranges[$index])) {
            unset($this->ranges[$index]);

            --$this->valuesCount;
        }

        return $this;
    }

    /**
     * @param Range $range
     *
     * @return static
     */
    public function addExcludedRange(Range $range)
    {
        if ($this->locked) {
            throw new ValuesStructureIsLocked();
        }

        $this->excludedRanges[] = $range;
        ++$this->valuesCount;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasExcludedRanges()
    {
        return count($this->excludedRanges) > 0;
    }

    /**
     * @return Range[]
     */
    public function getExcludedRanges()
    {
        return $this->excludedRanges;
    }

    /**
     * @param int $index
     *
     * @return static
     */
    public function removeExcludedRange($index)
    {
        if ($this->locked) {
            throw new ValuesStructureIsLocked();
        }

        if (isset($this->excludedRanges[$index])) {
            unset($this->excludedRanges[$index]);

            --$this->valuesCount;
        }

        return $this;
    }

    /**
     * @param Compare $value
     *
     * @return static
     */
    public function addComparison(Compare $value)
    {
        if ($this->locked) {
            throw new ValuesStructureIsLocked();
        }

        $this->comparisons[] = $value;
        ++$this->valuesCount;

        return $this;
    }

    /**
     * @return Compare[]
     */
    public function getComparisons()
    {
        return $this->comparisons;
    }

    /**
     * @return bool
     */
    public function hasComparisons()
    {
        return count($this->comparisons) > 0;
    }

    /**
     * @param int $index
     *
     * @return static
     */
    public function removeComparison($index)
    {
        if ($this->locked) {
            throw new ValuesStructureIsLocked();
        }

        if (isset($this->comparisons[$index])) {
            unset($this->comparisons[$index]);

            --$this->valuesCount;
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

    /**
     * @param PatternMatch $value
     *
     * @return static
     */
    public function addPatternMatch(PatternMatch $value)
    {
        if ($this->locked) {
            throw new ValuesStructureIsLocked();
        }

        $this->patternMatchers[] = $value;
        ++$this->valuesCount;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasPatternMatchers()
    {
        return count($this->patternMatchers) > 0;
    }

    /**
     * @param int $index
     *
     * @return static
     */
    public function removePatternMatch($index)
    {
        if ($this->locked) {
            throw new ValuesStructureIsLocked();
        }

        if (isset($this->patternMatchers[$index])) {
            unset($this->patternMatchers[$index]);

            --$this->valuesCount;
        }

        return $this;
    }

    /**
     * @param ValuesError $error
     *
     * @return static
     */
    public function addError(ValuesError $error)
    {
        if ($this->locked) {
            throw new ValuesStructureIsLocked();
        }

        $this->errors[$error->getHash()] = $error;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasErrors()
    {
        return count($this->errors) > 0;
    }

    /**
     * @return ValuesError[]
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @param ValuesError $error
     *
     * @return bool
     */
    public function hasError(ValuesError $error)
    {
        return isset($this->errors[$error->getHash()]);
    }

    /**
     * @param ValuesError $error
     *
     * @return static
     */
    public function removeError(ValuesError $error)
    {
        if (isset($this->errors[$error->getHash()])) {
            unset($this->errors[$error->getHash()]);
        }

        return $this;
    }

    /**
     * @return int
     */
    public function count()
    {
        return $this->valuesCount;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize([
            $this->excludedValues,
            $this->ranges,
            $this->excludedRanges,
            $this->comparisons,
            $this->singleValues,
            $this->patternMatchers,
            $this->valuesCount,
            $this->errors,
            $this->locked,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);

        list(
            $this->excludedValues,
            $this->ranges,
            $this->excludedRanges,
            $this->comparisons,
            $this->singleValues,
            $this->patternMatchers,
            $this->valuesCount,
            $this->errors,
            $this->locked
        ) = $data;
    }

    /**
     * Sets the values data is locked.
     *
     * After calling this method, setter methods can be no longer called.
     *
     * @param bool $locked
     *
     * @throws BadMethodCallException when the data is locked
     */
    public function setDataLocked($locked = true)
    {
        if ($this->locked) {
            throw new ValuesStructureIsLocked();
        }

        $this->locked = $locked;
    }

    /**
     * Returns whether the field's data is locked.
     *
     * A field with locked data is restricted to the data passed in
     * this configuration.
     *
     * @return bool Whether the data is locked.
     */
    public function isDataLocked()
    {
        return $this->locked;
    }
}
