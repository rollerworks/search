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

namespace Rollerworks\Component\Search\Extension\Core\ChoiceList;

/**
 * A list of choices with arbitrary data types.
 *
 * The user of this class is responsible for assigning string values to the
 * choices. Both the choices and their values are passed to the constructor.
 * Each choice must have a corresponding value (with the same array key) in
 * the value array.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ArrayChoiceList implements ChoiceList
{
    /**
     * @var array
     */
    protected $choices;

    /**
     * The values indexed by the original keys.
     *
     * @var array
     */
    protected $structuredValues;

    /**
     * @var int[]|string[]
     */
    protected $originalKeys;

    /**
     * The callback for creating the value for a choice.
     *
     * @var callable|null
     */
    protected $valueCallback;

    /**
     * @var bool
     */
    protected $valuesAreConstant;

    /**
     * The given choice array must have the same array keys as the value array.
     *
     * @param array|\Traversable $choices The selectable choices
     * @param callable|null      $value   The callable for creating the value
     *                                    for a choice. If `null` is passed,
     *                                    incrementing integers are used as
     *                                    values
     */
    public function __construct($choices, callable $value = null)
    {
        if ($choices instanceof \Traversable) {
            $choices = iterator_to_array($choices);
        }

        if (null === $value && $this->castableToString($choices)) {
            $this->valuesAreConstant = true;
            $value = function ($choice) {
                return false === $choice ? '0' : (string) $choice;
            };
        }

        if (null !== $value) {
            // If a deterministic value generator was passed, use it later
            $this->valueCallback = $value;
            $this->valuesAreConstant = true;
        } else {
            $this->valuesAreConstant = false;
            // Otherwise simply generate incrementing integers as values
            $i = 0;
            $value = function () use (&$i) {
                return $i++;
            };
        }

        // If the choices are given as recursive array (i.e. with explicit
        // choice groups), flatten the array. The grouping information is needed
        // in the view only.
        $this->flatten($choices, $value, $choicesByValues, $keysByValues, $structuredValues);

        $this->choices = $choicesByValues;
        $this->originalKeys = $keysByValues;
        $this->structuredValues = $structuredValues;
    }

    public function getChoices(): array
    {
        return $this->choices;
    }

    public function getValues(): array
    {
        return array_map('strval', array_keys($this->choices));
    }

    public function getStructuredValues(): array
    {
        return $this->structuredValues;
    }

    public function getOriginalKeys(): array
    {
        return $this->originalKeys;
    }

    public function getChoicesForValues(array $values): array
    {
        $choices = [];

        foreach ($values as $i => $givenValue) {
            if (\array_key_exists($givenValue, $this->choices)) {
                $choices[$i] = $this->choices[$givenValue];
            }
        }

        return $choices;
    }

    public function getValuesForChoices(array $choices): array
    {
        $values = [];

        // Use the value callback to compare choices by their values, if present
        if ($this->valueCallback) {
            $givenValues = [];

            foreach ($choices as $i => $givenChoice) {
                $givenValues[$i] = (string) \call_user_func($this->valueCallback, $givenChoice);
            }

            return array_intersect($givenValues, array_keys($this->choices));
        }

        // Otherwise compare choices by identity
        foreach ($choices as $i => $givenChoice) {
            foreach ($this->choices as $value => $choice) {
                if ($choice === $givenChoice) {
                    $values[$i] = (string) $value;

                    break;
                }
            }
        }

        return $values;
    }

    public function isValuesConstant(): bool
    {
        return $this->valuesAreConstant;
    }

    /**
     * Flattens an array into the given output variables.
     *
     * @param array      $choices          The array to flatten
     * @param callable   $value            The callable for generating choice values
     * @param array|null $choicesByValues  The flattened choices indexed by the
     *                                     corresponding values
     * @param array|null $keysByValues     The original keys indexed by the
     *                                     corresponding values
     * @param array|null $structuredValues The values indexed by the original keys
     */
    private function flatten(array $choices, callable $value, &$choicesByValues, &$keysByValues, &$structuredValues): void
    {
        if (null === $choicesByValues) {
            $choicesByValues = [];
            $keysByValues = [];
            $structuredValues = [];
        }

        foreach ($choices as $key => $choice) {
            if (\is_array($choice)) {
                $this->flatten($choice, $value, $choicesByValues, $keysByValues, $structuredValues[$key]);

                continue;
            }

            $choiceValue = (string) \call_user_func($value, $choice);
            $choicesByValues[$choiceValue] = $choice;
            $keysByValues[$choiceValue] = $key;
            $structuredValues[$key] = $choiceValue;
        }
    }

    /**
     * Checks whether the given choices can be cast to strings without
     * generating duplicates.
     *
     * @param array $choices
     * @param array $cache   The cache for previously checked entries. Internal
     */
    private function castableToString(array $choices, array &$cache = []): bool
    {
        foreach ($choices as $choice) {
            if (\is_array($choice)) {
                if (!$this->castableToString($choice, $cache)) {
                    return false;
                }

                continue;
            } elseif (!is_scalar($choice)) {
                return false;
            }

            $choice = false === $choice ? '0' : (string) $choice;

            if (isset($cache[$choice])) {
                return false;
            }

            $cache[$choice] = true;
        }

        return true;
    }
}
