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
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class SearchFactoryBuilder
{
    /**
     * @var ResolvedFieldTypeFactoryInterface
     */
    private $resolvedTypeFactory;

    /**
     * @var SearchExtensionInterface[]
     */
    private $extensions = [];

    /**
     * @var FieldTypeInterface[]
     */
    private $types = [];

    /**
     * @var @var array<FieldTypeExtensionInterface[]>
     */
    private $typeExtensions = [];

    /**
     * @var FieldSetRegistryInterface
     */
    private $fieldSetRegistry;

    /**
     * Sets the factory for creating ResolvedFieldTypeInterface instances.
     *
     * @param ResolvedFieldTypeFactoryInterface $resolvedTypeFactory
     *
     * @return $this The builder
     */
    public function setResolvedTypeFactory(ResolvedFieldTypeFactoryInterface $resolvedTypeFactory)
    {
        $this->resolvedTypeFactory = $resolvedTypeFactory;

        return $this;
    }

    /**
     * Adds an extension to be loaded by the factory.
     *
     * @param SearchExtensionInterface $extension The extension
     *
     * @return $this The builder
     */
    public function addExtension(SearchExtensionInterface $extension)
    {
        $this->extensions[] = $extension;

        return $this;
    }

    /**
     * Adds a list of extensions to be loaded by the factory.
     *
     * @param SearchExtensionInterface[] $extensions The extensions
     *
     * @return $this The builder
     */
    public function addExtensions(array $extensions)
    {
        $this->extensions = array_merge($this->extensions, $extensions);

        return $this;
    }

    /**
     * Adds a field type to the factory.
     *
     * @param FieldTypeInterface $type The field type
     *
     * @return $this The builder
     */
    public function addType(FieldTypeInterface $type)
    {
        $this->types[get_class($type)] = $type;

        return $this;
    }

    /**
     * Adds a list of field types to the factory.
     *
     * @param FieldTypeInterface[] $types The field types
     *
     * @return $this The builder
     */
    public function addTypes(array $types)
    {
        foreach ($types as $type) {
            $this->types[get_class($type)] = $type;
        }

        return $this;
    }

    /**
     * Adds a field type extension to the factory.
     *
     * @param FieldTypeExtensionInterface $typeExtension The field type extension
     *
     * @return $this The builder
     */
    public function addTypeExtension(FieldTypeExtensionInterface $typeExtension)
    {
        $this->typeExtensions[$typeExtension->getExtendedType()][] = $typeExtension;

        return $this;
    }

    /**
     * Adds a list of field type extension to the factory.
     *
     * @param FieldTypeExtensionInterface[] $typeExtensions The field type extension
     *
     * @return $this The builder
     */
    public function addTypeExtensions(array $typeExtensions)
    {
        foreach ($typeExtensions as $typeExtension) {
            $this->typeExtensions[$typeExtension->getExtendedType()][] = $typeExtension;
        }

        return $this;
    }

    /**
     * Builds and returns the factory.
     *
     * @return SearchFactoryInterface The search factory
     */
    public function getSearchFactory(): SearchFactoryInterface
    {
        $extensions = $this->extensions;

        if (count($this->types) > 0 || count($this->typeExtensions) > 0) {
            $extensions[] = new PreloadedExtension($this->types, $this->typeExtensions);
        }

        $resolvedTypeFactory = $this->resolvedTypeFactory ?: new ResolvedFieldTypeFactory();
        $registry = new FieldRegistry($extensions, $resolvedTypeFactory);

        return new SearchFactory($registry, $this->fieldSetRegistry ?: new FieldSetRegistry());
    }
}
