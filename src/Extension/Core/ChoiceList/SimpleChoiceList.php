<?php

/**
 * This file is part of RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Extension\Core\ChoiceList;

/**
 * A choice list for choices of type string or integer.
 *
 * Choices and their associated labels can be passed in a single array. Since
 * choices are passed as array keys, only strings or integer choices are
 * allowed.
 *
 * <code>
 * $choiceList = new SimpleChoiceList(array(
 *     'creditcard' => 'Credit card payment',
 *     'cash' => 'Cash payment',
 * ));
 * </code>
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class SimpleChoiceList extends ChoiceList
{
    /**
     * Creates a new simple choice list.
     *
     * @param array $choices The array of choices with the choices as keys and
     *                       the labels as values.
     */
    public function __construct(array $choices)
    {
        parent::__construct($choices, $choices);
    }

    /**
     * {@inheritDoc}
     */
    public function getChoiceForValue($value)
    {
        $value = $this->fixChoice($value);
        $values = $this->getValues();

        // The values are identical to the choices, so we can just return them
        // to improve performance a little bit
        if (array_key_exists($value, $values)) {
            return $value;
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getValueForChoice($choice)
    {
        $choice = $this->fixChoice($choice);
        $values = $this->getValues();

        // The choices are identical to the values, so we can just return them
        // to improve performance a little bit
        if (array_key_exists($choice, $values)) {
            return $choice;
        }

        return null;
    }

    /**
     * {@inheritDoc}
     */
    public function getLabelForChoice($choice)
    {
        $choice = $this->fixChoice($choice);
        $labels = $this->getLabels();

        // The choices are identical to the values, so we can just return them
        // to improve performance a little bit
        if (array_key_exists($choice, $labels)) {
            return $labels[$choice];
        }

        return null;
    }

    /**
     * Recursively adds the given choices to the list.
     *
     * Takes care of splitting the single $choices array passed in the
     * constructor into choices and labels.
     *
     * @param array|\Traversable $choices The list of choices.
     * @param array              $labels  Ignored.
     */
    protected function addChoices($choices, array $labels)
    {
        // Add choices to the nested buckets
        foreach ($choices as $choice => $label) {
            $this->addChoice($choice, $label);
        }
    }

    /**
     * Converts the choice to a valid PHP array key.
     *
     * @param mixed $choice The choice
     *
     * @return string|integer A valid PHP array key
     */
    protected function fixChoice($choice)
    {
        return $this->fixIndex($choice);
    }

    /**
     * {@inheritDoc}
     */
    protected function fixChoices(array $choices)
    {
        return $this->fixIndices($choices);
    }

    /**
     * {@inheritDoc}
     */
    protected function createIndex($choice)
    {
        return (string) $choice;
    }

    /**
     * {@inheritDoc}
     */
    protected function createValue($choice)
    {
        // Choices are guaranteed to be unique and scalar, so we can simply
        // convert them to strings
        return (string) $choice;
    }
}
