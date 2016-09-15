<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Extension\Core\ChoiceList;

use Rollerworks\Component\Search\Exception\InvalidArgumentException;
use Rollerworks\Component\Search\Exception\InvalidConfigurationException;
use Rollerworks\Component\Search\Exception\UnexpectedTypeException;

/**
 * A choice list for choices of arbitrary data types.
 *
 * Choices and labels are passed in two arrays. The indices of the choices
 * and the labels should match.
 *
 * <code>
 * $choices = array(true, false);
 * $labels = array('Agree', 'Disagree');
 * $choiceList = new ChoiceList($choices, $labels);
 * </code>
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class ChoiceList implements ChoiceListInterface
{
    /**
     * The choices with their indices as keys.
     *
     * @var array
     */
    protected $choices = [];

    /**
     * The choice values with the indices of the matching choices as keys.
     *
     * @var array
     */
    protected $values = [];

    /**
     * The choice values with the indices of the matching choices as keys.
     *
     * @var array
     */
    protected $labels = [];

    /**
     * Creates a new choice list.
     *
     * @param array|\Traversable $choices The array of choices
     * @param array              $labels  The array of labels. The structure of this array
     *                                    should match the structure of $choices
     *
     * @throws UnexpectedTypeException If the choices are not an array or \Traversable
     */
    public function __construct($choices, array $labels)
    {
        if (!$choices instanceof \Traversable && !is_array($choices)) {
            throw new UnexpectedTypeException($choices, ['array', '\Traversable']);
        }

        $this->initialize($choices, $labels);
    }

    /**
     * Initializes the list with choices.
     *
     * Safe to be called multiple times. The list is cleared on every call.
     *
     * @param array|\Traversable $choices The choices to write into the list
     * @param array              $labels  The labels belonging to the choices
     */
    protected function initialize($choices, array $labels)
    {
        $this->choices = [];
        $this->values = [];

        $this->addChoices($choices, $labels);
    }

    /**
     * {@inheritdoc}
     */
    public function getChoices()
    {
        return $this->choices;
    }

    /**
     * {@inheritdoc}
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * {@inheritdoc}
     */
    public function getChoiceForValue($givenValue)
    {
        $givenValue = $this->fixValue($givenValue);

        foreach ($this->values as $i => $value) {
            if ($value === $givenValue) {
                return $this->choices[$i];
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getValueForChoice($givenChoice)
    {
        $givenChoice = $this->fixValue($givenChoice);

        foreach ($this->choices as $i => $choice) {
            if ($choice === $givenChoice) {
                return $this->values[$i];
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getLabels()
    {
        return $this->labels;
    }

    /**
     * {@inheritdoc}
     */
    public function getChoiceForLabel($givenLabel)
    {
        foreach ($this->labels as $i => $value) {
            if ($value === $givenLabel) {
                return $this->choices[$i];
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getLabelForChoice($givenChoice)
    {
        $givenChoice = $this->fixValue($givenChoice);

        foreach ($this->choices as $i => $choice) {
            if ($choice === $givenChoice) {
                return $this->labels[$i];
            }
        }
    }

    /**
     * Returns whether the given variable contains a valid index.
     *
     * A name is accepted if it
     *
     * * is empty
     * * starts with a letter, digit or underscore
     * * contains only letters, digits, numbers, underscores ("_"),
     * hyphens ("-") and colons (":")
     *
     * @param string $name The tested name
     *
     * @return bool Whether the name is valid
     */
    public static function isValidName($name)
    {
        return '' === $name || null === $name || preg_match('/^[a-zA-Z0-9_][a-zA-Z0-9_\-:]*$/D', $name);
    }

    /**
     * Recursively adds the given choices to the list.
     *
     * @param array|\Traversable $choices The list of choices
     * @param array              $labels  The labels corresponding to the choices
     *
     * @throws InvalidArgumentException      If the structures of the choices and labels array do not match
     * @throws InvalidConfigurationException If no valid value or index could be created for a choice
     */
    protected function addChoices($choices, array $labels)
    {
        // Add choices to the nested buckets
        foreach ($choices as $index => $choice) {
            if (!array_key_exists($index, $labels)) {
                throw new InvalidArgumentException('The structures of the choices and labels array do not match.');
            }

            $this->addChoice($choice, $labels[$index]);
        }
    }

    /**
     * Adds a new choice.
     *
     * @param mixed  $choice The choice to add
     * @param string $label  The label for the choice
     *
     * @throws InvalidConfigurationException If no valid value or index could be created
     */
    protected function addChoice($choice, $label)
    {
        $index = $this->createIndex($choice);

        if ('' === $index || null === $index || !static::isValidName((string) $index)) {
            throw new InvalidConfigurationException(
                sprintf(
                    'The index "%s" created by the choice list is invalid. It should be a valid, non-empty name.',
                    $index
                )
            );
        }

        $value = $this->createValue($choice);

        if (!is_string($value)) {
            throw new InvalidConfigurationException(
                sprintf(
                    'The value created by the choice list is of type "%s", but should be a string.',
                    gettype($value)
                )
            );
        }

        $this->choices[$index] = $this->fixChoice($choice);
        $this->values[$index] = $value;
        $this->labels[$index] = $label;
    }

    /**
     * Creates a new unique index for this choice.
     *
     * Extension point to change the indexing strategy.
     *
     * @param mixed $choice The choice to create an index for
     *
     * @return int|string A unique index containing only ASCII letters,
     *                    digits and underscores
     */
    protected function createIndex($choice)
    {
        return count($this->choices);
    }

    /**
     * Creates a new unique value for this choice.
     *
     * By default, an integer is generated since it cannot be guaranteed that
     * all values in the list are convertible to (unique) strings. Subclasses
     * can override this behaviour if they can guarantee this property.
     *
     * @param mixed $choice The choice to create a value for
     *
     * @return string A unique string
     */
    protected function createValue($choice)
    {
        return (string) count($this->values);
    }

    /**
     * Fixes the data type of the given choice value to avoid comparison
     * problems.
     *
     * @param mixed $value The choice value
     *
     * @return string The value as string
     */
    protected function fixValue($value)
    {
        return (string) $value;
    }

    /**
     * Fixes the data type of the given choice index to avoid comparison
     * problems.
     *
     * @param mixed $index The choice index
     *
     * @return int|string The index as PHP array key
     */
    protected function fixIndex($index)
    {
        if (is_bool($index) || (string) (int) $index === (string) $index) {
            return (int) $index;
        }

        return (string) $index;
    }

    /**
     * Fixes the data types of the given choice indices to avoid comparison
     * problems.
     *
     * @param array $indices The choice indices
     *
     * @return array The indices as strings
     */
    protected function fixIndices(array $indices)
    {
        foreach ($indices as &$index) {
            $index = $this->fixIndex($index);
        }

        return $indices;
    }

    /**
     * Fixes the data type of the given choice to avoid comparison problems.
     *
     * Extension point. In this implementation, choices are guaranteed to
     * always maintain their type and thus can be type-safely compared.
     *
     * @param mixed $choice The choice
     *
     * @return mixed The fixed choice
     */
    protected function fixChoice($choice)
    {
        return $choice;
    }
}
