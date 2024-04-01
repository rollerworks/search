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

use Rollerworks\Component\Search\FieldSetView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A wrapper for a field type and its extensions.
 */
interface ResolvedFieldType
{
    public function getParent(): ?self;

    /**
     * Returns the wrapped field type.
     */
    public function getInnerType(): FieldType;

    /**
     * Returns the extensions of the wrapped field type.
     *
     * @return FieldTypeExtension[]
     */
    public function getTypeExtensions(): array;

    public function createField(string $name, array $options = []): FieldConfig;

    /**
     * This configures the {@link FieldConfig}.
     *
     * This method is called for each type in the hierarchy starting from the
     * top most type. Type extensions can further modify the field.
     */
    public function buildType(FieldConfig $config, array $options): void;

    /**
     * Creates a new SearchFieldView for a field of this type.
     */
    public function createFieldView(FieldConfig $config, FieldSetView $view): SearchFieldView;

    /**
     * Configures a SearchFieldView for the type hierarchy.
     */
    public function buildFieldView(SearchFieldView $view, FieldConfig $config, array $options): void;

    /**
     * Returns the prefix of the template block name for this type.
     */
    public function getBlockPrefix(): string;

    /**
     * Returns the configured options resolver used for this type.
     */
    public function getOptionsResolver(): OptionsResolver;
}
