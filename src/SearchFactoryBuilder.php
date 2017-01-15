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
     * @var @var array<FieldTypeExtensionInterface[]>
     */
    private $typeExtensions = [];

    /**
     * @var FieldSetRegistry
     */
    private $fieldSetRegistry;

    /**
     * @var SearchConditionOptimizer
     */
    private $conditionOptimizer;

    /**
     * Sets the factory for creating ResolvedFieldTypeInterface instances.
     *
     * @param ResolvedFieldTypeFactory $resolvedTypeFactory
     *
     * @return $this The builder
     */
    public function setResolvedTypeFactory(ResolvedFieldTypeFactory $resolvedTypeFactory)
    {
        $this->resolvedTypeFactory = $resolvedTypeFactory;

        return $this;
    }

    /**
     * Sets the default SearchCondition optimizer.
     *
     * @param SearchConditionOptimizer $conditionOptimizer
     *
     * @return $this The builder
     */
    public function setSearchConditionOptimizer(SearchConditionOptimizer $conditionOptimizer)
    {
        $this->conditionOptimizer = $conditionOptimizer;

        return $this;
    }

    /**
     * Adds an extension to be loaded by the factory.
     *
     * @param SearchExtension $extension The extension
     *
     * @return $this The builder
     */
    public function addExtension(SearchExtension $extension)
    {
        $this->extensions[] = $extension;

        return $this;
    }

    /**
     * Adds a list of extensions to be loaded by the factory.
     *
     * @param SearchExtension[] $extensions The extensions
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
     * @param FieldType $type The field type
     *
     * @return $this The builder
     */
    public function addType(FieldType $type)
    {
        $this->types[get_class($type)] = $type;

        return $this;
    }

    /**
     * Adds a list of field types to the factory.
     *
     * @param FieldType[] $types The field types
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
     * @param FieldTypeExtension $typeExtension The field type extension
     *
     * @return $this The builder
     */
    public function addTypeExtension(FieldTypeExtension $typeExtension)
    {
        $this->typeExtensions[$typeExtension->getExtendedType()][] = $typeExtension;

        return $this;
    }

    /**
     * Adds a list of field type extension to the factory.
     *
     * @param FieldTypeExtension[] $typeExtensions The field type extension
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
     * @return SearchFactory The search factory
     */
    public function getSearchFactory(): SearchFactory
    {
        $extensions = $this->extensions;

        if (count($this->types) > 0 || count($this->typeExtensions) > 0) {
            $extensions[] = new PreloadedExtension($this->types, $this->typeExtensions);
        }

        $resolvedTypeFactory = $this->resolvedTypeFactory ?? new GenericResolvedFieldTypeFactory();
        $registry = new GenericTypeRegistry($extensions, $resolvedTypeFactory);

        return new GenericSearchFactory($registry, $this->fieldSetRegistry ?? new LazyFieldSetRegistry(), $this->conditionOptimizer);
    }
}
