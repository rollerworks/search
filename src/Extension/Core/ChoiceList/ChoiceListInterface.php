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

/**
 * Contains choices that can be selected in a search field.
 *
 * Each choice has three different properties:
 *
 *    - Choice: The choice that should be returned to the application by the
 *              choice field. Can be any scalar value or an object, but no
 *              array.
 *    - Label:  A text representing the choice that is displayed to the user.
 *    - Value:  A uniquely identifying value that can contain arbitrary
 *              characters, but no arrays or objects. This value is displayed
 *              in the HTML "value" attribute.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface ChoiceListInterface
{
    /**
     * Returns the list of choices.
     *
     * @return array The choices with their indices as keys
     */
    public function getChoices();

    /**
     * Returns the values for the choices.
     *
     * @return array The values with the corresponding choice indices as keys
     */
    public function getValues();

    /**
     * Returns the labels for the choices.
     *
     * @return array The labels with the corresponding choice indices as keys
     */
    public function getLabels();

    /**
     * Returns the choices corresponding to the given value.
     *
     * The choice can have any data type.
     *
     * @param string $value
     *
     * @return mixed
     */
    public function getChoiceForValue($value);

    /**
     * Returns the value corresponding to the given choice.
     *
     * The value must be a string.
     *
     * @param string $choice
     *
     * @return string The value of the the choice
     */
    public function getValueForChoice($choice);

    /**
     * Returns the choice corresponding to the given label.
     *
     * The choice can have any data type.
     *
     * @param string $label
     *
     * @return string The choice
     */
    public function getChoiceForLabel($label);

    /**
     * Returns the label corresponding to the given choice.
     *
     * The value must be a string.
     *
     * @param string $choice
     *
     * @return string The choice label
     */
    public function getLabelForChoice($choice);
}
