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

namespace Rollerworks\Component\Search\Field;

use Rollerworks\Component\Search\DataTransformer;
use Rollerworks\Component\Search\FieldSetView;
use Rollerworks\Component\Search\ValueComparator;

/**
 * The configuration of a SearchField.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface FieldConfig
{
    public function getName(): string;

    /**
     * Returns the field type used to construct the field.
     */
    public function getType(): ResolvedFieldType;

    /**
     * Returns whether value-type $type is accepted by the field.
     *
     * $type must be a FQCN of a class implementing
     * {@link \Rollerworks\Component\Search\Value\ValueHolder}.
     */
    public function supportValueType(string $type): bool;

    /**
     * Sets whether value-type $type is accepted by the field.
     *
     * $type must be a FQCN of a class implementing
     * {@link \Rollerworks\Component\Search\Value\ValueHolder}.
     */
    public function setValueTypeSupport(string $type, bool $enabled);

    /**
     * Set the {@link ValueComparator} instance for validation.
     */
    public function setValueComparator(ValueComparator $comparator);

    /**
     * Returns the configured {@link ValueComparator} instance.
     */
    public function getValueComparator(): ?ValueComparator;

    /**
     * Sets a view transformer for the field.
     *
     * * The reverseTransform method of the transformer is used to convert
     *   data from the model to the view format.
     *
     * * The transform method of the transformer is used to convert from the
     *   view to the model format.
     *
     * @param DataTransformer|null $viewTransformer Use null to remove the transformer
     */
    public function setViewTransformer(?DataTransformer $viewTransformer = null);

    /**
     * Returns the view transformer of the field.
     */
    public function getViewTransformer(): ?DataTransformer;

    /**
     * Sets a normalize transformer for the field.
     *
     * * The transform method of the transformer is used to convert data from the
     *   normalized to the model format.
     *
     * * The reverseTransform method of the transformer is used to convert from the
     *   model to the normalized format.
     *
     * @param DataTransformer|null $viewTransformer Use null to remove the transformer
     */
    public function setNormTransformer(?DataTransformer $viewTransformer = null);

    /**
     * Returns the normalize transformer of the field.
     */
    public function getNormTransformer(): ?DataTransformer;

    /**
     * Returns whether the field's data is locked.
     *
     * A field with locked data is restricted to the data passed in
     * this configuration.
     */
    public function isConfigLocked(): bool;

    /**
     * Returns all options passed during the construction of the field.
     */
    public function getOptions(): array;

    public function hasOption(string $name): bool;

    /**
     * Returns the value of a specific option.
     *
     * @param mixed|null $default
     */
    public function getOption(string $name, $default = null);

    /**
     * Returns a new SearchFieldView for the SearchField.
     */
    public function createView(FieldSetView $fieldSet): SearchFieldView;

    /**
     * Sets the value for an attribute.
     */
    public function setAttribute(string $name, $value);

    /**
     * Sets the attributes.
     */
    public function setAttributes(array $attributes);

    /**
     * Returns additional attributes of the field.
     *
     * @return array An array of key-value combinations
     */
    public function getAttributes(): array;

    /**
     * Returns whether the attribute with the given name exists.
     */
    public function hasAttribute(string $name): bool;

    /**
     * Returns the value of the given attribute.
     *
     * @param mixed $default The value returned if the attribute does not exist
     */
    public function getAttribute(string $name, $default = null);
}
