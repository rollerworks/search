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

use Rollerworks\Component\Search\Exception\UnexpectedTypeException;

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
    public function createField($name, $type, array $options = [])
    {
        $field = $this->createFieldBuilder($name, $type, $options);

        return $field;
    }

    /**
     * {@inheritdoc}
     */
    public function createFieldSetBuilder($name)
    {
        $fieldSetBuilder = new FieldSetBuilder($name, $this);

        return $fieldSetBuilder;
    }

    /**
     * Creates a new {@link SearchField} instance.
     *
     * @param string                    $name
     * @param string|FieldTypeInterface $type
     * @param array                     $options
     *
     * @throws UnexpectedTypeException
     *
     * @return SearchField
     */
    private function createFieldBuilder($name, $type = 'field', array $options = [])
    {
        if ($type instanceof FieldTypeInterface) {
            $type = $this->resolveType($type);
        } elseif (is_string($type)) {
            $type = $this->registry->getType($type);
        } elseif (!$type instanceof ResolvedFieldTypeInterface) {
            throw new UnexpectedTypeException(
                $type,
                [
                     'string',
                     'Rollerworks\Component\Search\ResolvedFieldTypeInterface',
                     'Rollerworks\Component\Search\FieldTypeInterface',
                 ]
            );
        }

        $field = $type->createField($name, $options);

        // Explicitly call buildType() in order to be able to override either
        // createField() or buildType() in the resolved field type
        $type->buildType($field, $field->getOptions());

        return $field;
    }

    /**
     * Wraps a type into a ResolvedFieldTypeInterface implementation and connects
     * it with its parent type.
     *
     * @param FieldTypeInterface $type The type to resolve
     *
     * @return ResolvedFieldTypeInterface The resolved type
     */
    private function resolveType(FieldTypeInterface $type)
    {
        $parentType = $type->getParent();

        if ($parentType instanceof FieldTypeInterface) {
            $parentType = $this->resolveType($parentType);
        } elseif (null !== $parentType) {
            $parentType = $this->registry->getType($parentType);
        }

        return $this->resolvedTypeFactory->createResolvedType(
            $type, // Type extensions are not supported for unregistered type instances,
            // i.e. type instances that are passed to the SearchFactory directly,
            // nor for their parents, if getParent() also returns a type instance.
            [],
            $parentType
        );
    }
}
