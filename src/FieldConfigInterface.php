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
     * @return string the Field name
     */
    public function getName(): string;

    /**
     * Returns the field type used to construct the field.
     *
     * @return ResolvedFieldTypeInterface The field's type
     */
    public function getType(): ResolvedFieldTypeInterface;

    /**
     * Returns whether value-type $type is accepted by the field.
     *
     * $type must be a FQCN of a class implementing
     * {@link \Rollerworks\Component\Search\Value\ValueHolder}.
     *
     * @param string $type
     *
     * @return bool
     */
    public function supportValueType(string $type): bool;

    /**
     * Sets whether value-type $type is accepted by the field.
     *
     * $type must be a FQCN of a class implementing
     * {@link \Rollerworks\Component\Search\Value\ValueHolder}.
     *
     * @param string $type
     * @param bool   $enabled
     */
    public function setValueTypeSupport(string $type, bool $enabled);

    /**
     * Set the {@link ValueComparisonInterface} instance for optimizing
     * and validation.
     *
     * @param ValueComparisonInterface $comparisonObj
     */
    public function setValueComparison(ValueComparisonInterface $comparisonObj);

    /**
     * Returns the configured {@link ValueComparisonInterface} instance.
     *
     * @return ValueComparisonInterface|null
     */
    public function getValueComparison();

    /**
     * Sets a view transformer for the field.
     *
     * The reverseTransform method of the transformer is used to convert data from the
     * model to the view format.
     * The transform method of the transformer is used to convert from the
     * view to the model format.
     *
     * @param DataTransformerInterface|null $viewTransformer Use null to remove the
     *                                                       transformer
     */
    public function setViewTransformer(DataTransformerInterface $viewTransformer = null);

    /**
     * Returns the view transformer of the field.
     *
     * @return DataTransformerInterface|null
     */
    public function getViewTransformer();

    /**
     * Sets a normalize transformer for the field.
     *
     * The transform method of the transformer is used to convert data from the
     * normalized to the model format.
     * The reverseTransform method of the transformer is used to convert from the
     * model to the normalized format.
     *
     * @param DataTransformerInterface|null $viewTransformer Use null to remove the
     *                                                       transformer
     */
    public function setNormTransformer(DataTransformerInterface $viewTransformer = null);

    /**
     * Returns the normalize transformer of the field.
     *
     * @return DataTransformerInterface|null
     */
    public function getNormTransformer();

    /**
     * Returns whether the field's data is locked.
     *
     * A field with locked data is restricted to the data passed in
     * this configuration.
     *
     * @return bool Whether the data is locked
     */
    public function isConfigLocked(): bool;

    /**
     * Returns all options passed during the construction of the field.
     *
     * @return array The passed options
     */
    public function getOptions(): array;

    /**
     * Returns whether a specific option exists.
     *
     * @param string $name The option name
     *
     * @return bool Whether the option exists
     */
    public function hasOption(string $name): bool;

    /**
     * Returns the value of a specific option.
     *
     * @param string $name    The option name
     * @param mixed  $default The value returned if the option does not exist
     *
     * @return mixed The option value
     */
    public function getOption(string $name, $default = null);

    /**
     * Returns a new SearchFieldView for the SearchField.
     *
     * @return SearchFieldView
     */
    public function createView(): SearchFieldView;
}
