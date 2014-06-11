<?php

/**
 * This file is part of RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search;

/**
 * The configuration of a SearchField.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface FieldConfigInterface
{
    /**
     * Returns the name of field.
     *
     * @return string the Field name.
     */
    public function getName();

    /**
     * Returns the field type used to construct the field.
     *
     * @return ResolvedFieldTypeInterface The field's type.
     */
    public function getType();

    /**
     * Returns whether ranges are accepted.
     *
     * @return boolean
     */
    public function acceptRanges();

    /**
     * Returns whether comparisons are accepted.
     *
     * @return boolean
     */
    public function acceptCompares();

    /**
     * Returns whether the field is required to have values.
     *
     * @return boolean
     */
    public function isRequired();

    /**
     * Returns the Model's fully qualified class-name.
     *
     * This is required for certain storage engines.
     *
     * @return string
     */
    public function getModelRefClass();

    /**
     * Returns the Model field property-name.
     *
     * This is required for certain storage engines.
     *
     * @return string
     */
    public function getModelRefProperty();

    /**
     * Set the {@link ValueComparisonInterface} instance.
     *
     * @param ValueComparisonInterface $comparisonObj
     */
    public function setValueComparison(ValueComparisonInterface $comparisonObj);

    /**
     * Returns the configured {@link ValueComparisonInterface} instance.
     *
     * @return ValueComparisonInterface
     */
    public function getValueComparison();

    /**
     * Appends / prepends a transformer to the view transformer chain.
     *
     * The transform method of the transformer is used to convert data from the
     * normalized to the view format.
     * The reverseTransform method of the transformer is used to convert from the
     * view to the normalized format.
     *
     * @param DataTransformerInterface $viewTransformer
     * @param Boolean                  $forcePrepend    if set to true, prepend instead of appending
     *
     * @return self The configuration object.
     */
    public function addViewTransformer(DataTransformerInterface $viewTransformer, $forcePrepend = false);

    /**
     * Clears the view transformers.
     *
     * @return self The configuration object.
     */
    public function resetViewTransformers();

    /**
     * Returns the view transformers of the field.
     *
     * @return DataTransformerInterface[] An array of {@link DataTransformerInterface} instances.
     */
    public function getViewTransformers();

    /**
     * Returns whether the field's data is locked.
     *
     * A field with locked data is restricted to the data passed in
     * this configuration.
     *
     * @return Boolean Whether the data is locked.
     */
    public function getDataLocked();

    /**
     * Returns all options passed during the construction of the field.
     *
     * @return array The passed options.
     */
    public function getOptions();

    /**
     * Returns whether a specific option exists.
     *
     * @param string $name The option name,
     *
     * @return Boolean Whether the option exists.
     */
    public function hasOption($name);

    /**
     * Returns the value of a specific option.
     *
     * @param string $name    The option name.
     * @param mixed  $default The value returned if the option does not exist.
     *
     * @return mixed The option value.
     */
    public function getOption($name, $default = null);
}
