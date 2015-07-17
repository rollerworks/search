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

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface SearchFactoryBuilderInterface
{
    /**
     * Sets the factory for creating ResolvedFieldTypeInterface instances.
     *
     * @param ResolvedFieldTypeFactoryInterface $resolvedTypeFactory
     *
     * @return SearchFactoryBuilderInterface The builder
     */
    public function setResolvedTypeFactory(ResolvedFieldTypeFactoryInterface $resolvedTypeFactory);

    /**
     * Adds an extension to be loaded by the factory.
     *
     * @param SearchExtensionInterface $extension The extension.
     *
     * @return SearchFactoryBuilderInterface The builder
     */
    public function addExtension(SearchExtensionInterface $extension);

    /**
     * Adds a list of extensions to be loaded by the factory.
     *
     * @param SearchExtensionInterface[] $extensions The extensions.
     *
     * @return SearchFactoryBuilderInterface The builder
     */
    public function addExtensions(array $extensions);

    /**
     * Adds a field type to the factory.
     *
     * @param FieldTypeInterface $type The field type.
     *
     * @return SearchFactoryBuilderInterface The builder
     */
    public function addType(FieldTypeInterface $type);

    /**
     * Adds a list of field types to the factory.
     *
     * @param FieldTypeInterface[] $types The field types.
     *
     * @return SearchFactoryBuilderInterface The builder
     */
    public function addTypes(array $types);

    /**
     * Adds a field type extension to the factory.
     *
     * @param FieldTypeExtensionInterface $typeExtension The field type extension.
     *
     * @return SearchFactoryBuilderInterface The builder
     */
    public function addTypeExtension(FieldTypeExtensionInterface $typeExtension);

    /**
     * Builds and returns the factory.
     *
     * @return SearchFactoryInterface The search factory
     */
    public function getSearchFactory();
}
