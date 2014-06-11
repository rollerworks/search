<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search\Extension\Core\ChoiceList;

use Rollerworks\Component\Search\Exception\InvalidArgumentException;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * A choice list for object choices.
 *
 * Supports generation of choice labels, choice groups and choice values
 * by calling getters of the object (or associated objects).
 *
 * <code>
 * $choices = array($user1, $user2);
 *
 * // call getName() to determine the choice labels
 * $choiceList = new ObjectChoiceList($choices, 'name');
 * </code>
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ObjectChoiceList extends ChoiceList
{
    /**
     * @var PropertyAccessorInterface
     */
    private $propertyAccessor;

    /**
     * The property path used to obtain the choice label.
     *
     * @var PropertyPath
     */
    private $labelPath;

    /**
     * The property path used to obtain the choice value.
     *
     * @var PropertyPath
     */
    private $valuePath;

    /**
     * Creates a new object choice list.
     *
     * @param array|\Traversable        $choices          The array of choices.
     * @param string                    $labelPath        A property path pointing to the property used
     *                                                    for the choice labels. The value is obtained
     *                                                    by calling the getter on the object. If the
     *                                                    path is NULL, the object's __toString() method
     *                                                    is used instead.
     * @param string                    $valuePath        A property path pointing to the property used
     *                                                    for the choice values. If not given, integers
     *                                                    are generated instead.
     * @param PropertyAccessorInterface $propertyAccessor The reflection graph for reading property paths.
     */
    public function __construct($choices, $labelPath = null, $valuePath = null, PropertyAccessorInterface $propertyAccessor = null)
    {
        $this->propertyAccessor = $propertyAccessor ?: PropertyAccess::createPropertyAccessor();
        $this->labelPath = null !== $labelPath ? new PropertyPath($labelPath) : null;
        $this->valuePath = null !== $valuePath ? new PropertyPath($valuePath) : null;

        parent::__construct($choices, array());
    }

    /**
     * Initializes the list with choices.
     *
     * Safe to be called multiple times. The list is cleared on every call.
     *
     * @param array|\Traversable $choices The choices to write into the list.
     * @param array              $labels  Ignored.
     */
    protected function initialize($choices, array $labels)
    {
        $labels = array();

        $this->extractLabels($choices, $labels);

        parent::initialize($choices, $labels);
    }

    /**
     * Creates a new unique value for this choice.
     *
     * If a property path for the value was given at object creation,
     * the getter behind that path is now called to obtain a new value.
     * Otherwise a new integer is generated.
     *
     * @param mixed $choice The choice to create a value for
     *
     * @return integer|string A unique value without character limitations.
     */
    protected function createValue($choice)
    {
        if ($this->valuePath) {
            return (string) $this->propertyAccessor->getValue($choice, $this->valuePath);
        }

        return parent::createValue($choice);
    }

    /**
     * @param array|\Traversable $choices
     * @param array              $labels
     *
     * @throws InvalidArgumentException
     */
    private function extractLabels($choices, array &$labels)
    {
        foreach ($choices as $i => $choice) {
            if ($this->labelPath) {
                $labels[$i] = $this->propertyAccessor->getValue($choice, $this->labelPath);
            } elseif (method_exists($choice, '__toString')) {
                $labels[$i] = (string) $choice;
            } else {
                throw new InvalidArgumentException(sprintf('A "__toString()" method was not found on the objects of type "%s" passed to the choice field. To read a custom getter instead, set the argument $labelPath to the desired property path.', get_class($choice)));
            }
        }
    }
}
