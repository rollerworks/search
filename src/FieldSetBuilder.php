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

use Rollerworks\Component\Search\Exception\BadMethodCallException;
use Rollerworks\Component\Search\Exception\InvalidArgumentException;
use Rollerworks\Component\Search\Exception\UnexpectedTypeException;
use Rollerworks\Component\Search\Metadata\MetadataReaderInterface;

/**
 * The FieldSetBuilder helps with building a {@link FieldSet}.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class FieldSetBuilder implements FieldSetBuilderInterface
{
    /**
     * @var bool
     */
    private $locked;

    /**
     * Name of the FieldSet.
     *
     * @var string
     */
    private $name;

    /**
     * @var FieldConfigInterface[]
     */
    private $fields = [];

    /**
     * @var array[]
     */
    private $unresolvedFields = [];

    /**
     * @var SearchFactoryInterface
     */
    private $searchFactory;

    /**
     * @var MetadataReaderInterface
     */
    private $metadataReader;

    /**
     * Constructor.
     *
     * @param string                  $name           Name of the FieldSet
     * @param SearchFactoryInterface  $searchFactory  Search factory for creating new search fields
     * @param MetadataReaderInterface $metadataReader Optional metadata reader for loading field configuration
     */
    public function __construct($name, SearchFactoryInterface $searchFactory, MetadataReaderInterface $metadataReader = null)
    {
        $this->name = $name;
        $this->searchFactory = $searchFactory;
        $this->metadataReader = $metadataReader;
    }

    /**
     * Get the configured name for the FieldSet.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function add($field, $type = null, array $options = [], $required = false, $modelClass = null, $modelProperty = null)
    {
        if ($this->locked) {
            throw new BadMethodCallException(
                'FieldSetBuilder methods cannot be accessed anymore once the builder is turned into a FieldSet instance.'
            );
        }

        if (!$field instanceof FieldConfigInterface && !is_string($field)) {
            throw new UnexpectedTypeException($field, 'string or Rollerworks\Component\Search\FieldConfigInterface');
        }

        if ($field instanceof FieldConfigInterface) {
            $this->fields[$field->getName()] = $field;
            unset($this->unresolvedFields[$field->getName()]);

            return $this;
        }

        if (!$type instanceof FieldTypeInterface && !is_string($type)) {
            throw new UnexpectedTypeException($type, 'string or Rollerworks\Component\Search\FieldTypeInterface');
        }

        if (null !== $modelClass) {
            $options = array_merge(
                $options,
                [
                    'model_class' => $modelClass,
                    'model_property' => $modelProperty,
                ]
            );
        }

        $this->unresolvedFields[$field] = [
            'type' => $type,
            'options' => $options,
        ];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($name)
    {
        if ($this->locked) {
            throw new BadMethodCallException(
                'FieldSetBuilder methods cannot be accessed anymore once the builder is turned into a FieldSet instance.'
            );
        }

        unset($this->fields[$name], $this->unresolvedFields[$name]);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function has($name)
    {
        if ($this->locked) {
            throw new BadMethodCallException(
                'FieldSetBuilder methods cannot be accessed anymore once the builder is turned into a FieldSet instance.'
            );
        }

        if (isset($this->unresolvedFields[$name])) {
            return true;
        }

        if (isset($this->fields[$name])) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function get($name)
    {
        if ($this->locked) {
            throw new BadMethodCallException(
                'FieldSetBuilder methods cannot be accessed anymore once the builder is turned into a FieldSet instance.'
            );
        }

        if (isset($this->unresolvedFields[$name])) {
            $this->fields[$name] = $this->searchFactory->createField(
                $name,
                $this->unresolvedFields[$name]['type'],
                $this->unresolvedFields[$name]['options']
            );

            unset($this->unresolvedFields[$name]);
        }

        if (isset($this->fields[$name])) {
            return $this->fields[$name];
        }

        throw new InvalidArgumentException(sprintf('The field with the name "%s" does not exist.', $name));
    }

    /**
     * Import the fields configuration using the metadata reader.
     *
     * @param string $class   Model Class-name to load metadata from
     * @param array  $include List of field names to use, everything else is excluded
     * @param array  $exclude List of field names to exclude, everything else is included
     *
     * @throws BadMethodCallException When the FieldSet has been already turned into a FieldSet instance
     * @throws BadMethodCallException When no metadata reader is configured
     *
     * @return self
     */
    public function importFromClass($class, array $include = [], array $exclude = [])
    {
        if ($this->locked) {
            throw new BadMethodCallException(
                'FieldSetBuilder methods cannot be accessed anymore once the builder is turned into a FieldSet instance.'
            );
        }

        if (!$this->metadataReader) {
            throw new BadMethodCallException(
                'FieldSetBuilder is unable to import configuration from class, no MetadataReader was set for the constructor.'
            );
        }

        foreach ($this->metadataReader->getSearchFields($class) as $field) {
            if (($include && !in_array($field->fieldName, $include, true)) xor ($exclude && in_array($field->fieldName, $exclude, true))) {
                continue;
            }

            $field->options = array_merge(
                $field->options,
                [
                    'model_class' => $field->class,
                    'model_property' => $field->property,
                ]
            );

            $this->unresolvedFields[$field->fieldName] = [
                'type' => $field->type,
                'options' => $field->options,
            ];
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldSet()
    {
        if ($this->locked) {
            throw new BadMethodCallException(
                'FieldSetBuilder methods cannot be accessed anymore once the builder is turned into a FieldSet instance.'
            );
        }

        foreach ($this->unresolvedFields as $name => $field) {
            $this->fields[$name] = $this->searchFactory->createField(
                $name,
                $field['type'],
                $field['options']
            );

            unset($this->unresolvedFields[$name]);
        }

        $fieldSet = new FieldSet($this->name);

        foreach ($this->fields as $name => $field) {
            $fieldSet->set($name, $field);
        }

        $this->locked = true;

        return $fieldSet;
    }
}
