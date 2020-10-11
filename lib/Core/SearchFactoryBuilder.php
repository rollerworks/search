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

use Rollerworks\Component\Search\Field\FieldType;
use Rollerworks\Component\Search\Field\FieldTypeExtension;
use Rollerworks\Component\Search\Field\GenericResolvedFieldTypeFactory;
use Rollerworks\Component\Search\Field\GenericTypeRegistry;
use Rollerworks\Component\Search\Field\ResolvedFieldTypeFactory;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class SearchFactoryBuilder
{
    /**
     * @var ResolvedFieldTypeFactory
     */
    private $resolvedTypeFactory;

    /**
     * @var SearchExtension[]
     */
    private $extensions = [];

    /**
     * @var FieldType[]
     */
    private $types = [];

    /**
     * @var array
     */
    private $typeExtensions = [];

    /**
     * @var FieldSetRegistry
     */
    private $fieldSetRegistry;

    /**
     * Sets the factory for creating ResolvedFieldTypeInterface instances.
     *
     * @return static The builder
     */
    public function setResolvedTypeFactory(ResolvedFieldTypeFactory $resolvedTypeFactory)
    {
        $this->resolvedTypeFactory = $resolvedTypeFactory;

        return $this;
    }

    /**
     * Set the FieldSetRegistry to use for loading FieldSetConfigurators.
     *
     * @return SearchFactoryBuilder
     */
    public function setFieldSetRegistry(FieldSetRegistry $fieldSetRegistry): self
    {
        $this->fieldSetRegistry = $fieldSetRegistry;

        return $this;
    }

    /**
     * Adds an extension to be loaded by the factory.
     *
     * @return static The builder
     */
    public function addExtension(SearchExtension $extension)
    {
        $this->extensions[] = $extension;

        return $this;
    }

    /**
     * Adds a list of extensions to be loaded by the factory.
     *
     * @param SearchExtension[] $extensions
     *
     * @return static The builder
     */
    public function addExtensions(array $extensions)
    {
        $this->extensions = \array_merge($this->extensions, $extensions);

        return $this;
    }

    /**
     * Adds a field type to the factory.
     *
     * @return static The builder
     */
    public function addType(FieldType $type)
    {
        $this->types[\get_class($type)] = $type;

        return $this;
    }

    /**
     * Adds a list of field types to the factory.
     *
     * @param FieldType[] $types
     *
     * @return static The builder
     */
    public function addTypes(array $types)
    {
        foreach ($types as $type) {
            $this->types[\get_class($type)] = $type;
        }

        return $this;
    }

    /**
     * Adds a field type extension to the factory.
     *
     * @return static The builder
     */
    public function addTypeExtension(FieldTypeExtension $typeExtension)
    {
        $this->typeExtensions[$typeExtension->getExtendedType()][] = $typeExtension;

        return $this;
    }

    /**
     * Adds a list of field type extension to the factory.
     *
     * @param FieldTypeExtension[] $typeExtensions
     *
     * @return static The builder
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
     */
    public function getSearchFactory(): SearchFactory
    {
        $extensions = $this->extensions;

        if (\count($this->types) > 0 || \count($this->typeExtensions) > 0) {
            $extensions[] = new PreloadedExtension($this->types, $this->typeExtensions);
        }

        $resolvedTypeFactory = $this->resolvedTypeFactory ?? new GenericResolvedFieldTypeFactory();
        $registry = new GenericTypeRegistry($extensions, $resolvedTypeFactory);

        return new GenericSearchFactory($registry, $this->fieldSetRegistry ?? LazyFieldSetRegistry::create());
    }
}
