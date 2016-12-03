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
class SearchFactory implements SearchFactoryInterface
{
    /**
     * @var FieldRegistryInterface
     */
    private $registry;

    /**
     * @var ResolvedFieldTypeFactoryInterface
     */
    private $resolvedTypeFactory;

    /**
     * Constructor.
     *
     * @param FieldRegistryInterface            $registry
     * @param ResolvedFieldTypeFactoryInterface $resolvedTypeFactory
     */
    public function __construct(FieldRegistryInterface $registry, ResolvedFieldTypeFactoryInterface $resolvedTypeFactory)
    {
        $this->registry = $registry;
        $this->resolvedTypeFactory = $resolvedTypeFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function createField($name, string $type, array $options = [])
    {
        $type = $this->registry->getType($type);
        $field = $type->createField($name, $options);

        // Explicitly call buildType() in order to be able to override either
        // createField() or buildType() in the resolved field type
        $type->buildType($field, $field->getOptions());

        return $field;
    }

    /**
     * {@inheritdoc}
     */
    public function createFieldSetBuilder()
    {
        return new FieldSetBuilder($this);
    }
}
