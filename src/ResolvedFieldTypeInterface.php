<?php

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
interface ResolvedFieldTypeInterface
{
    /**
     * Returns the name of the type.
     *
     * @return string The type name.
     */
    public function getName();

    /**
     * Returns the parent type.
     *
     * @return ResolvedFieldTypeInterface|null The parent type or null
     */
    public function getParent();

    /**
     * Returns the wrapped field type.
     *
     * @return FieldTypeInterface The wrapped field type
     */
    public function getInnerType();

    /**
     * Returns the extensions of the wrapped field type.
     *
     * @return FieldTypeExtensionInterface[]
     */
    public function getTypeExtensions();

    /**
     * Returns a new FieldConfigInterface instance.
     *
     * @param string $name
     * @param array  $options
     *
     * @return FieldConfigInterface
     */
    public function createField($name, array $options = []);

    /**
     * This configures the {@link FieldConfigInterface}.
     *
     * This method is called for each type in the hierarchy starting from the
     * top most type. Type extensions can further modify the field.
     *
     * @param FieldConfigInterface $config
     * @param array                $options
     */
    public function buildType(FieldConfigInterface $config, array $options);

    /**
     * Creates a new SearchFieldView for a field of this type.
     *
     * @param FieldConfigInterface $config
     *
     * @return SearchFieldView
     */
    public function createFieldView(FieldConfigInterface $config);

    /**
     * Configures a SearchFieldView for the type hierarchy.
     *
     * @param SearchFieldView      $view
     * @param FieldConfigInterface $config
     * @param array                $options
     */
    public function buildFieldView(SearchFieldView $view, FieldConfigInterface $config, array $options);

    /**
     * Returns the configured options resolver used for this type.
     *
     * @return OptionsResolver The options resolver
     */
    public function getOptionsResolver();
}
