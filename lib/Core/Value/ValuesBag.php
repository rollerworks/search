<?php

declare(strict_types=1);

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Value;

/**
 * A ValuesBag holds all the values per type.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class ValuesBag implements \Countable, \Serializable
{
    private $valuesCount = 0;
    private $simpleValues = [];
    private $simpleExcludedValues = [];
    private $values = [];

    /**
     * @return int
     */
    public function count(?string $type = null): int
    {
        if (null === $type) {
            return $this->valuesCount;
        }

        switch ($type) {
            case 'simpleValues':
            case 'simpleValue':
                return \count($this->simpleValues);
            case 'simpleExcludedValues':
            case 'simpleExcludedValue':
                return \count($this->simpleExcludedValues);
            default:
                return \count($this->values[$type] ?? []);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function serialize(): string
    {
        return serialize(
            [
                $this->simpleValues,
                $this->simpleExcludedValues,
                $this->values,
                $this->valuesCount,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized): void
    {
        $data = unserialize($serialized);

        list(
            $this->simpleValues,
            $this->simpleExcludedValues,
            $this->values,
            $this->valuesCount
        ) = $data;
    }

    /**
     * @return array
     */
    public function getSimpleValues(): array
    {
        return $this->simpleValues;
    }

    /**
     * @param mixed $value
     *
     * @return static
     */
    public function addSimpleValue($value)
    {
        $this->simpleValues[] = $value;

        ++$this->valuesCount;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasSimpleValues(): bool
    {
        return count($this->simpleValues) > 0;
    }

    /**
     * @param int $index
     *
     * @return static
     */
    public function removeSimpleValue(int $index)
    {
        if (isset($this->simpleValues[$index])) {
            unset($this->simpleValues[$index]);

            --$this->valuesCount;
        }

        return $this;
    }

    /**
     * @return array
     */
    public function getExcludedSimpleValues(): array
    {
        return $this->simpleExcludedValues;
    }

    /**
     * @param mixed $value
     *
     * @return static
     */
    public function addExcludedSimpleValue($value)
    {
        $this->simpleExcludedValues[] = $value;
        ++$this->valuesCount;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasExcludedSimpleValues(): bool
    {
        return count($this->simpleExcludedValues) > 0;
    }

    /**
     * Remove a simple excluded value by index.
     *
     * @param int $index
     *
     * @return static
     */
    public function removeExcludedSimpleValue(int $index)
    {
        if (isset($this->simpleExcludedValues[$index])) {
            unset($this->simpleExcludedValues[$index]);

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
    public function get(string $type): array
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
    public function has(string $type): bool
    {
        return isset($this->values[$type]) && count($this->values[$type]) > 0;
    }

    /**
     * Remove a value by type and index.
     *
     * @param string $type
     * @param int    $index
     *
     * @return ValuesBag New ValuesBag object with the referenced values removed
     */
    public function remove(string $type, int $index)
    {
        if (isset($this->values[$type][$index])) {
            unset($this->values[$type][$index]);

            --$this->valuesCount;
        }

        return $this;
    }

    /**
     * Add a value to the bag.
     *
     * @param ValueHolder $value
     *
     * @return static
     */
    public function add(ValueHolder $value)
    {
        $this->values[get_class($value)][] = $value;

        ++$this->valuesCount;

        return $this;
    }

    public function all(): array
    {
        return $this->values;
    }
}
