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
 * A list of choices that can be selected in a choice field.
 *
 * A choice list assigns unique string values to each of a list of choices.
 * These string values are displayed in the "value" attributes in HTML and
 * submitted back to the server.
 *
 * The acceptable data types for the choices depend on the implementation.
 * Values must always be strings and (within the list) free of duplicates.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface ChoiceList
{
    /**
     * Returns all selectable choices.
     *
     * @return array The selectable choices indexed by the corresponding values
     */
    public function getChoices(): array;

    /**
     * Returns the values for the choices.
     *
     * The values are strings that do not contain duplicates.
     *
     * @return string[]
     */
    public function getValues(): array;

    /**
     * Returns the values in the structure originally passed to the list.
     *
     * Contrary to {@link getValues()}, the result is indexed by the original
     * keys of the choices. If the original array contained nested arrays, these
     * nested arrays are represented here as well:
     *
     *     $builder->add('field', ChoiceType::class, [
     *         'choices' => [
     *             'Decided' => ['Yes' => true, 'No' => false],
     *             'Undecided' => ['Maybe' => null],
     *         ],
     *     ]);
     *
     * In this example, the result of this method is:
     *
     *     [
     *         'Decided' => ['Yes' => '0', 'No' => '1'],
     *         'Undecided' => ['Maybe' => '2'],
     *     ]
     *
     * @return string[] The choice values
     */
    public function getStructuredValues(): array;

    /**
     * Returns the original keys of the choices.
     *
     * The original keys are the keys of the choice array that was passed in the
     * "choice" option of the choice type. Note that this array may contain
     * duplicates if the "choice" option contained choice groups:
     *
     *     $builder->add('field', ChoiceType::class, [
     *         'choices' => [
     *             'Decided' => [true, false],
     *             'Undecided' => [null],
     *         ],
     *     ]);
     *
     * In this example, the original key 0 appears twice, once for `true` and
     * once for `null`.
     *
     * @return int[]|string[] The original choice keys indexed by the
     *                        corresponding choice values
     */
    public function getOriginalKeys(): array;

    /**
     * Returns the choices corresponding to the given values.
     *
     * The choices are returned with the same keys and in the same order as the
     * corresponding values in the given array.
     *
     * @param string[] $values An array of choice values. Non-existing values in
     *                         this array are ignored
     */
    public function getChoicesForValues(array $values): array;

    /**
     * Returns the values corresponding to the given choices.
     *
     * The values are returned with the same keys and in the same order as the
     * corresponding choices in the given array.
     *
     * @param array $choices An array of choices. Non-existing choices in this
     *                       array are ignored
     *
     * @return string[] An array of choice values
     */
    public function getValuesForChoices(array $choices): array;

    /**
     * Returns whether the values are constant (not dependent of there position).
     *
     * Whenever the 'generated' values are dependent on there position
     * (like an incremented list). This method must return false.
     *
     * This method is used to determine whether the value or label must
     * be used for the normalized input format. And ensures that input always
     * relates to the correct choice and the value doesn't change whenever
     * the provided order or length changes (between requests).
     */
    public function isValuesConstant(): bool;
}
