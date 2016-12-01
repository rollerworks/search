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
use Rollerworks\Component\Search\Value\ExcludedRange;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\SingleValue;
use Rollerworks\Component\Search\Value\ValueHolder;

/**
 * A ValuesBag holds all the values per type.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class ValuesBag implements \Countable, \Serializable
{
    /**
     * @deprecated Deprecated since version 1.2, to be removed in 2.0.
     *             Use `Rollerworks\Component\Search\Value\Range::class` instead
     */
    const VALUE_TYPE_RANGE = 'range';

    /**
     * @deprecated Deprecated since version 1.2, to be removed in 2.0.
     *             Use `Rollerworks\Component\Search\Value\Compare::class` instead
     */
    const VALUE_TYPE_COMPARISON = 'comparison';

    /**
     * @deprecated Deprecated since version 1.2, to be removed in 2.0.
     *             Use `Rollerworks\Component\Search\Value\PatternMatch::class` instead
     */
    const VALUE_TYPE_PATTERN_MATCH = 'pattern-match';

    private $simpleValues = [];
    private $simpleExcludedValues = [];
    private $simpleValuesObjects = [];
    private $simpleExcludedValuesObjects = [];
    private $values = [];

    private $valuesCount = 0;
    private $errors = [];
    private $locked = false;

    /**
     * @return SingleValue[]
     *
     * @deprecated Deprecated since version 1.2, to be removed in 2.0. Use getSimpleValues() instead
     */
    public function getSingleValues()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.2 and will be removed in 2.0. Use getSimpleValue() instead.', E_USER_DEPRECATED);

        return $this->simpleValuesObjects;
    }

    /**
     * @param SingleValue $value
     *
     * @return $this
     *
     * @deprecated Deprecated since version 1.2, to be removed in 2.0. Use addSimpleValue() instead
     */
    public function addSingleValue(SingleValue $value)
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.2 and will be removed in 2.0. Use addSimpleValue() instead.', E_USER_DEPRECATED);

        if ($this->locked) {
            throw new ValuesStructureIsLocked();
        }

        $this->simpleValuesObjects[] = $value;
        $this->simpleValues[] = $value->getValue();

        ++$this->valuesCount;

        return $this;
    }

    /**
     * @return bool
     *
     * @deprecated Deprecated since version 1.2, to be removed in 2.0. Use hasSimpleValues() instead
     */
    public function hasSingleValues()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.2 and will be removed in 2.0. Use hasSimpleValue() instead.', E_USER_DEPRECATED);

        return count($this->simpleValues) > 0;
    }

    /**
     * @param int $index
     *
     * @return $this
     *
     * @deprecated Deprecated since version 1.2, to be removed in 2.0. Use removeSimpleValues() instead
     */
    public function removeSingleValue($index)
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.2 and will be removed in 2.0. Use removeSimpleValue() instead.', E_USER_DEPRECATED);

        return $this->removeSimpleValue($index);
    }

    /**
     * @param SingleValue $value
     *
     * @return $this
     *
     * @deprecated Deprecated since version 1.2, to be removed in 2.0. Use addExcludedSimpleValue() instead
     */
    public function addExcludedValue(SingleValue $value)
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.2 and will be removed in 2.0. Use addExcludedSimpleValue() instead.', E_USER_DEPRECATED);

        if ($this->locked) {
            throw new ValuesStructureIsLocked();
        }

        $this->simpleExcludedValues[] = $value->getValue();
        $this->simpleExcludedValuesObjects[] = $value;
        ++$this->valuesCount;

        return $this;
    }

    /**
     * @return bool
     *
     * @deprecated Deprecated since version 1.2, to be removed in 2.0. Use hasExcludedSimpleValues() instead
     */
    public function hasExcludedValues()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.2 and will be removed in 2.0. Use hasExcludedSimpleValue() instead.', E_USER_DEPRECATED);

        return count($this->simpleExcludedValues) > 0;
    }

    /**
     * @return SingleValue[]
     *
     * @deprecated Deprecated since version 1.2, to be removed in 2.0. Use getExcludedSimpleValues() instead
     */
    public function getExcludedValues()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.2 and will be removed in 2.0. Use getExcludedSimpleValue() instead.', E_USER_DEPRECATED);

        return $this->simpleExcludedValuesObjects;
    }

    /**
     * @param int $index
     *
     * @return $this
     *
     * @deprecated Deprecated since version 1.2, to be removed in 2.0. Use getExcludedSimpleValues() instead
     */
    public function removeExcludedValue($index)
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.2 and will be removed in 2.0. Use removeExcludedSimpleValue() instead.', E_USER_DEPRECATED);

        if ($this->locked) {
            throw new ValuesStructureIsLocked();
        }

        if (isset($this->simpleExcludedValues[$index])) {
            unset($this->simpleExcludedValues[$index], $this->simpleExcludedValuesObjects[$index]);

            --$this->valuesCount;
        }

        return $this;
    }

    /**
     * @param Range $range
     *
     * @return $this
     *
     * @deprecated Deprecated since version 1.2, to be removed in 2.0. Use add() instead
     */
    public function addRange(Range $range)
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.2 and will be removed in 2.0. Use add() instead.', E_USER_DEPRECATED);

        return $this->add($range);
    }

    /**
     * @return bool
     *
     * @deprecated Deprecated since version 1.2, to be removed in 2.0. Use has() instead
     */
    public function hasRanges()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.2 and will be removed in 2.0. Use has() instead.', E_USER_DEPRECATED);

        return $this->has('Rollerworks\Component\Search\Value\Range');
    }

    /**
     * @return Range[]
     *
     * @deprecated Deprecated since version 1.2, to be removed in 2.0. Use get() instead
     */
    public function getRanges()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.2 and will be removed in 2.0. Use get() instead.', E_USER_DEPRECATED);

        return $this->get('Rollerworks\Component\Search\Value\Range');
    }

    /**
     * @param int $index
     *
     * @return $this
     *
     * @deprecated Deprecated since version 1.2, to be removed in 2.0. Use remove() instead
     */
    public function removeRange($index)
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.2 and will be removed in 2.0. Use remove() instead.', E_USER_DEPRECATED);

        return $this->remove('Rollerworks\Component\Search\Value\Range', $index);
    }

    /**
     * @param Range $range
     *
     * @return $this
     *
     * @deprecated Deprecated since version 1.2, to be removed in 2.0. Use add() instead
     */
    public function addExcludedRange(Range $range)
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.2 and will be removed in 2.0. Use add() instead.', E_USER_DEPRECATED);

        if (!$range instanceof ExcludedRange) {
            $range = new ExcludedRange(
                $range->getLower(),
                $range->getUpper(),
                $range->isLowerInclusive(),
                $range->isUpperInclusive(),
                $range->getViewLower(),
                $range->getViewUpper()
            );
        }

        return $this->add($range);
    }

    /**
     * @return bool
     *
     * @deprecated Deprecated since version 1.2, to be removed in 2.0. Use has() instead
     */
    public function hasExcludedRanges()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.2 and will be removed in 2.0. Use has() instead.', E_USER_DEPRECATED);

        return $this->has('Rollerworks\Component\Search\Value\ExcludedRange');
    }

    /**
     * @return Range[]
     *
     * @deprecated Deprecated since version 1.2, to be removed in 2.0. Use get() instead
     */
    public function getExcludedRanges()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.2 and will be removed in 2.0. Use get() instead.', E_USER_DEPRECATED);

        return $this->get('Rollerworks\Component\Search\Value\ExcludedRange');
    }

    /**
     * @param int $index
     *
     * @return $this
     *
     * @deprecated Deprecated since version 1.2, to be removed in 2.0. Use remove() instead
     */
    public function removeExcludedRange($index)
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.2 and will be removed in 2.0. Use remove() instead.', E_USER_DEPRECATED);

        return $this->remove('Rollerworks\Component\Search\Value\ExcludedRange', $index);
    }

    /**
     * @param Compare $value
     *
     * @return $this
     *
     * @deprecated Deprecated since version 1.2, to be removed in 2.0. Use add() instead
     */
    public function addComparison(Compare $value)
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.2 and will be removed in 2.0. Use add() instead.', E_USER_DEPRECATED);

        return $this->add($value);
    }

    /**
     * @return Compare[]
     *
     * @deprecated Deprecated since version 1.2, to be removed in 2.0. Use get() instead
     */
    public function getComparisons()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.2 and will be removed in 2.0. Use get() instead.', E_USER_DEPRECATED);

        return $this->get('Rollerworks\Component\Search\Value\Compare');
    }

    /**
     * @return bool
     *
     * @deprecated Deprecated since version 1.2, to be removed in 2.0. Use has() instead
     */
    public function hasComparisons()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.2 and will be removed in 2.0. Use has() instead.', E_USER_DEPRECATED);

        return $this->has('Rollerworks\Component\Search\Value\Compare');
    }

    /**
     * @param int $index
     *
     * @return $this
     *
     * @deprecated Deprecated since version 1.2, to be removed in 2.0. Use remove() instead
     */
    public function removeComparison($index)
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.2 and will be removed in 2.0. Use remove() instead.', E_USER_DEPRECATED);

        return $this->remove('Rollerworks\Component\Search\Value\Compare', $index);
    }

    /**
     * @return PatternMatch[]
     *
     * @deprecated Deprecated since version 1.2, to be removed in 2.0. Use get() instead
     */
    public function getPatternMatchers()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.2 and will be removed in 2.0. Use get() instead.', E_USER_DEPRECATED);

        return $this->get('Rollerworks\Component\Search\Value\PatternMatch');
    }

    /**
     * @param PatternMatch $value
     *
     * @return $this
     *
     * @deprecated Deprecated since version 1.2, to be removed in 2.0. Use add() instead
     */
    public function addPatternMatch(PatternMatch $value)
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.2 and will be removed in 2.0. Use add() instead.', E_USER_DEPRECATED);

        return $this->add($value);
    }

    /**
     * @return bool
     *
     * @deprecated Deprecated since version 1.2, to be removed in 2.0. Use has() instead
     */
    public function hasPatternMatchers()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.2 and will be removed in 2.0. Use has() instead.', E_USER_DEPRECATED);

        return $this->has('Rollerworks\Component\Search\Value\PatternMatch');
    }

    /**
     * @param int $index
     *
     * @return $this
     *
     * @deprecated Deprecated since version 1.2, to be removed in 2.0. Use remove() instead
     */
    public function removePatternMatch($index)
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.2 and will be removed in 2.0. Use remove() instead.', E_USER_DEPRECATED);

        return $this->remove('Rollerworks\Component\Search\Value\PatternMatch', $index);
    }

    /**
     * @param ValuesError $error
     *
     * @return static
     *
     * @deprecated Deprecated since version 1.2, to be removed in 2.0
     */
    public function addError(ValuesError $error)
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.2 and will be removed in 2.0.', E_USER_DEPRECATED);

        if ($this->locked) {
            throw new ValuesStructureIsLocked();
        }

        $this->errors[$error->getHash()] = $error;

        return $this;
    }

    /**
     * @return bool
     *
     * @deprecated Deprecated since version 1.2, to be removed in 2.0
     */
    public function hasErrors()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.2 and will be removed in 2.0.', E_USER_DEPRECATED);

        return count($this->errors) > 0;
    }

    /**
     * @return ValuesError[]
     *
     * @deprecated Deprecated since version 1.2, to be removed in 2.0
     */
    public function getErrors()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.2 and will be removed in 2.0.', E_USER_DEPRECATED);

        return $this->errors;
    }

    /**
     * @param ValuesError $error
     *
     * @return bool
     *
     * @deprecated Deprecated since version 1.2, to be removed in 2.0
     */
    public function hasError(ValuesError $error)
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.2 and will be removed in 2.0.', E_USER_DEPRECATED);

        return isset($this->errors[$error->getHash()]);
    }

    /**
     * @param ValuesError $error
     *
     * @return static
     *
     * @deprecated Deprecated since version 1.2, to be removed in 2.0
     */
    public function removeError(ValuesError $error)
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.2 and will be removed in 2.0.', E_USER_DEPRECATED);

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
        return serialize(
            [
                $this->simpleValues,
                $this->simpleValuesObjects,
                $this->simpleExcludedValues,
                $this->simpleExcludedValuesObjects,
                $this->values,
                $this->valuesCount,
                $this->errors,
                $this->locked,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);

        list(
            $this->simpleValues,
            $this->simpleValuesObjects,
            $this->simpleExcludedValues,
            $this->simpleExcludedValuesObjects,
            $this->values,
            $this->valuesCount,
            $this->errors,
            $this->locked) = $data;
    }

    /**
     * Sets the values data is locked.
     *
     * After calling this method, setter methods can be no longer called.
     *
     * @param bool $locked
     *
     * @throws BadMethodCallException when the data is locked
     *
     * @deprecated Deprecated since version 1.2, to be removed in 2.0. Use ensureDataLocked() instead
     */
    public function setDataLocked($locked = true)
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since version 1.2 and will be removed in 2.0. Use ensureDataLocked() instead.', E_USER_DEPRECATED);

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
     * @return bool Whether the data is locked
     */
    public function isDataLocked()
    {
        return $this->locked;
    }

    /**
     * Sets the data is locked (if not already locked).
     *
     * A ValuesBag with locked data is restricted to the current data.
     *
     * @return $this
     */
    public function ensureDataLocked()
    {
        $this->locked = true;

        return $this;
    }

    /**
     * @return array
     */
    public function getSimpleValues()
    {
        return $this->simpleValues;
    }

    /**
     * @param mixed $value
     *
     * @return $this
     */
    public function addSimpleValue($value)
    {
        if ($this->locked) {
            throw new ValuesStructureIsLocked();
        }

        $this->simpleValuesObjects[] = new SingleValue($value);
        $this->simpleValues[] = $value;

        ++$this->valuesCount;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasSimpleValues()
    {
        return count($this->simpleValues) > 0;
    }

    /**
     * @param int $index
     *
     * @return $this
     */
    public function removeSimpleValue($index)
    {
        if ($this->locked) {
            throw new ValuesStructureIsLocked();
        }

        if (isset($this->simpleValues[$index])) {
            unset($this->simpleValues[$index], $this->simpleValuesObjects[$index]);

            --$this->valuesCount;
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getExcludedSimpleValues()
    {
        return $this->simpleExcludedValues;
    }

    /**
     * @param mixed $value
     *
     * @return $this
     */
    public function addExcludedSimpleValue($value)
    {
        if ($this->locked) {
            throw new ValuesStructureIsLocked();
        }

        $this->simpleExcludedValues[] = $value;
        $this->simpleExcludedValuesObjects[] = new SingleValue($value);
        ++$this->valuesCount;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasExcludedSimpleValues()
    {
        return count($this->simpleExcludedValues) > 0;
    }

    /**
     * Remove a simple excluded value by index.
     *
     * @param int $index
     *
     * @return $this
     */
    public function removeExcludedSimpleValue($index)
    {
        if ($this->locked) {
            throw new ValuesStructureIsLocked();
        }

        if (isset($this->simpleExcludedValues[$index])) {
            unset($this->simpleExcludedValues[$index], $this->simpleExcludedValuesObjects[$index]);

            --$this->valuesCount;
        }

        return $this;
    }

    /**
     * Get all values from a specific type.
     *
     * @param string $type
     *
     * @return ValueHolder[]
     */
    public function get($type)
    {
        if (!isset($this->values[$type])) {
            return [];
        }

        return $this->values[$type];
    }

    /**
     * Get a single value by type and index.
     *
     * @param string $type
     *
     * @return bool
     */
    public function has($type)
    {
        return isset($this->values[$type]) && count($this->values[$type]) > 0;
    }

    /**
     * Remove a value by type and index.
     *
     * @param string    $type
     * @param int|int[] $index
     *
     * @return ValuesBag New ValuesBag object with the referenced values removed
     */
    public function remove($type, $index)
    {
        if ($this->locked) {
            throw new ValuesStructureIsLocked();
        }

        if (isset($this->values[$type][$index])) {
            unset($this->values[$type][$index]);

            --$this->valuesCount;
        }

        return $this;
    }

    /**
     * Add a value to the bag.
     *
     * @param ValueHolder|ValueHolder[] $value
     *
     * @return ValuesBag New ValuesBag object with the values added
     */
    public function add(ValueHolder $value)
    {
        if ($this->locked) {
            throw new ValuesStructureIsLocked();
        }

        $this->values[get_class($value)][] = $value;

        ++$this->valuesCount;

        return $this;
    }
}
