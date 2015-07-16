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

use Rollerworks\Component\Search\Metadata\MetadataReaderInterface;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class SearchFactoryBuilder implements SearchFactoryBuilderInterface
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
     * @var MetadataReaderInterface
     */
    private $mappingReader;

    /**
     * {@inheritdoc}
     */
    public function setResolvedTypeFactory(ResolvedFieldTypeFactoryInterface $resolvedTypeFactory)
    {
        $this->resolvedTypeFactory = $resolvedTypeFactory;

        return $this;
    }
    /**
     * {@inheritdoc}
     */
    public function setMetaReader(MetadataReaderInterface $mappingReader)
    {
        $this->mappingReader = $mappingReader;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addExtension(SearchExtensionInterface $extension)
    {
        $this->extensions[] = $extension;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addExtensions(array $extensions)
    {
        $this->extensions = array_merge($this->extensions, $extensions);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addType(FieldTypeInterface $type)
    {
        $this->types[$type->getName()] = $type;

        return $this;
    }

    /**
     * @param FieldTypeInterface[] $types
     *
     * @return $this
     */
    public function addTypes(array $types)
    {
        foreach ($types as $type) {
            $this->types[$type->getName()] = $type;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function addTypeExtension(FieldTypeExtensionInterface $typeExtension)
    {
        $this->typeExtensions[$typeExtension->getExtendedType()][] = $typeExtension;

        return $this;
    }

    /**
     * @param FieldTypeExtensionInterface[] $typeExtensions
     *
     * @return $this
     */
    public function addTypeExtensions(array $typeExtensions)
    {
        foreach ($typeExtensions as $typeExtension) {
            $this->typeExtensions[$typeExtension->getExtendedType()][] = $typeExtension;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchFactory()
    {
        $extensions = $this->extensions;

        if (count($this->types) > 0 || count($this->typeExtensions) > 0) {
            $extensions[] = new PreloadedExtension($this->types, $this->typeExtensions);
        }

        $resolvedTypeFactory = $this->resolvedTypeFactory ?: new ResolvedFieldTypeFactory();
        $registry = new FieldRegistry($extensions, $resolvedTypeFactory);

        return new SearchFactory($registry, $resolvedTypeFactory, $this->mappingReader);
    }
}
