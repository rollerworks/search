<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search;

use Metadata\Driver\DriverInterface;
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
     * @var DriverInterface
     */
    private $mappingReader;

    /**
     * @var ResolvedFieldTypeFactoryInterface
     */
    private $resolvedTypeFactory;

    /**
     * Constructor.
     *
     * @param FieldRegistryInterface            $registry
     * @param ResolvedFieldTypeFactoryInterface $resolvedTypeFactory
     * @param DriverInterface                   $mappingReader
     */
    public function __construct(FieldRegistryInterface $registry, ResolvedFieldTypeFactoryInterface $resolvedTypeFactory, DriverInterface $mappingReader = null)
    {
        $this->registry = $registry;
        $this->resolvedTypeFactory = $resolvedTypeFactory;
        $this->mappingReader = $mappingReader;
    }

    /**
     * Create a new search field.
     *
     * @param string  $name
     * @param string  $type
     * @param array   $options
     * @param boolean $required
     *
     * @return ResolvedFieldTypeInterface
     */
    public function createField($name, $type, array $options = array(), $required = false)
    {
        $field = $this->createFieldBuilder($name, $type, $options);
        $field->setRequired($required);

        return $field;
    }

    /**
     * Create a new search field referenced by property.
     *
     * @param string  $class
     * @param string  $property
     * @param string  $name
     * @param string  $type
     * @param array   $options
     * @param boolean $required
     *
     * @return ResolvedFieldTypeInterface
     */
    public function createFieldForProperty($class, $property, $name, $type, array $options = array(), $required = false)
    {
        $field = $this->createFieldBuilder($name, $type, $options);
        $field->setModelRef($class, $property);
        $field->setRequired($required);

        return $field;
    }

    /**
     * Create a new FieldsetBuilderInterface instance.
     *
     * @param string $name
     *
     * @return FieldsetBuilder Interface
     */
    public function createFieldSetBuilder($name)
    {
        $fieldSetBuilder = new FieldSetBuilder($name, $this, $this->mappingReader);

        return $fieldSetBuilder;
    }

    /**
     * Creates a new {@link SearchField} instance.
     *
     * @param string $name
     * @param string $type
     * @param array  $options
     *
     * @return SearchField
     *
     * @throws UnexpectedTypeException
     */
    private function createFieldBuilder($name, $type = 'field', array $options = array())
    {
        if ($type instanceof FieldTypeInterface) {
            $type = $this->resolveType($type);
        } elseif (is_string($type)) {
            $type = $this->registry->getType($type);
        } elseif (!$type instanceof ResolvedFieldTypeInterface) {
            throw new UnexpectedTypeException($type, 'string, Rollerworks\Component\Search\ResolvedFieldTypeInterface or Rollerworks\Component\Search\FieldTypeInterface');
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
     * @param FieldTypeInterface $type The type to resolve.
     *
     * @return ResolvedFieldTypeInterface The resolved type.
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
            array(),
            $parentType
        );
    }
}
