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

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A wrapper for a field type and its extensions.
 */
interface ResolvedFieldType
{
    /**
     * Returns the parent type.
     *
     * @return ResolvedFieldType|null The parent type or null
     */
    public function getParent();

    /**
     * Returns the wrapped field type.
     *
     * @return FieldType The wrapped field type
     */
    public function getInnerType(): FieldType;

    /**
     * Returns the extensions of the wrapped field type.
     *
     * @return FieldTypeExtension[]
     */
    public function getTypeExtensions(): array;

    /**
     * Returns a new FieldConfigInterface instance.
     *
     * @param string $name
     * @param array  $options
     *
     * @return FieldConfig
     */
    public function createField(string $name, array $options = []): FieldConfig;

    /**
     * This configures the {@link FieldConfigInterface}.
     *
     * This method is called for each type in the hierarchy starting from the
     * top most type. Type extensions can further modify the field.
     *
     * @param FieldConfig $config
     * @param array       $options
     */
    public function buildType(FieldConfig $config, array $options);

    /**
     * Creates a new SearchFieldView for a field of this type.
     *
     * @param FieldConfig  $config
     * @param FieldSetView $view
     *
     * @return SearchFieldView
     */
    public function createFieldView(FieldConfig $config, FieldSetView $view);

    /**
     * Configures a SearchFieldView for the type hierarchy.
     *
     * @param SearchFieldView $view
     * @param FieldConfig     $config
     * @param array           $options
     */
    public function buildFieldView(SearchFieldView $view, FieldConfig $config, array $options);

    /**
     * Returns the configured options resolver used for this type.
     *
     * @return OptionsResolver The options resolver
     */
    public function getOptionsResolver(): OptionsResolver;
}
