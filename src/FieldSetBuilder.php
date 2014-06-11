<?php

/**
 * This file is part of RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search;

use Metadata\MetadataFactoryInterface;
use Rollerworks\Component\Search\Exception\BadMethodCallException;
use Rollerworks\Component\Search\Exception\InvalidArgumentException;
use Rollerworks\Component\Search\Exception\UnexpectedTypeException;
use Rollerworks\Component\Search\Metadata\PropertyMetadata;

/**
 * A builder for creating {@link FieldSet} instances.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class FieldSetBuilder implements FieldSetBuilderInterface
{
    /**
     * @var boolean
     */
    private $locked;

    /**
     * @var string
     */
    private $name;

    /**
     * @var FieldConfigInterface[]
     */
    private $fields = array();

    /**
     * @var array[]
     */
    private $unresolvedFields = array();

    /**
     * @var SearchFactoryInterface
     */
    private $searchFactory;

    /**
     * @var MetadataFactoryInterface
     */
    private $mappingReader;

    /**
     * @param string                   $name
     * @param SearchFactoryInterface   $searchFactory
     * @param MetadataFactoryInterface $mappingReader
     */
    public function __construct($name, SearchFactoryInterface $searchFactory, MetadataFactoryInterface $mappingReader = null)
    {
        $this->name = $name;
        $this->searchFactory = $searchFactory;
        $this->mappingReader = $mappingReader;
    }

    /**
     * Get the configured name for the FieldSet.
     *
     * @return string
     *
     * @throws BadMethodCallException When the FieldSet is already generated.
     */
    public function getName()
    {
        if ($this->locked) {
            throw new BadMethodCallException('FieldSetBuilder methods cannot be accessed anymore once the builder is turned into a FieldSet instance.');
        }

        return $this->name;
    }

    /**
     * @param string|FieldConfigInterface $field
     * @param string|FieldTypeInterface   $type
     * @param array                       $options
     * @param boolean                     $required
     * @param string                      $modelClass
     * @param string                      $property
     *
     * @return self
     *
     * @throws BadMethodCallException  When the FieldSet is already generated.
     * @throws UnexpectedTypeException
     */
    public function add($field, $type = null, array $options = array(), $required = false, $modelClass = null, $property = null)
    {
        if ($this->locked) {
            throw new BadMethodCallException('FieldSetBuilder methods cannot be accessed anymore once the builder is turned into a FieldSet instance.');
        }

        if (!is_string($field) && !$field instanceof FieldConfigInterface) {
            throw new UnexpectedTypeException($field, 'string or Rollerworks\Component\Search\FieldConfigInterface');
        }

        if ($field instanceof FieldConfigInterface) {
            $this->fields[$field->getName()] = $field;
            unset($this->unresolvedFields[$field->getName()]);

            return $this;
        }

        if (!is_string($type) && !$type instanceof FieldTypeInterface) {
            throw new UnexpectedTypeException($type, 'string or Rollerworks\Component\Search\FieldTypeInterface');
        }

        $this->unresolvedFields[$field] = array(
            'type' => $type,
            'options' => $options,
            'required' => $required,
            'class' => $modelClass,
            'property' => $property
        );

        return $this;
    }

    /**
     * @param string $name
     *
     * @return self
     *
     * @throws BadMethodCallException
     */
    public function remove($name)
    {
        if ($this->locked) {
            throw new BadMethodCallException('FieldSetBuilder methods cannot be accessed anymore once the builder is turned into a FieldSet instance.');
        }

        unset($this->fields[$name]);
        unset($this->unresolvedFields[$name]);

        return $this;
    }

    /**
     * @param string $name
     *
     * @return boolean
     *
     * @throws BadMethodCallException
     */
    public function has($name)
    {
        if ($this->locked) {
            throw new BadMethodCallException('FieldSetBuilder methods cannot be accessed anymore once the builder is turned into a FieldSet instance.');
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
     * @param string $name
     *
     * @return FieldConfigInterface|array
     *
     * @throws BadMethodCallException
     * @throws InvalidArgumentException
     */
    public function get($name)
    {
        if ($this->locked) {
            throw new BadMethodCallException('FieldSetBuilder methods cannot be accessed anymore once the builder is turned into a FieldSet instance.');
        }

        if (isset($this->unresolvedFields[$name])) {
            return $this->unresolvedFields[$name];
        }

        if (isset($this->fields[$name])) {
            return $this->fields[$name];
        }

        throw new InvalidArgumentException(sprintf('The field with the name "%s" does not exist.', $name));
    }

    /**
     * @param string $class
     * @param array  $include List of field names to use, everything else is excluded
     * @param array  $exclude List of field names to exclude
     *
     * @return self
     *
     * @throws BadMethodCallException
     */
    public function importFromClass($class, array $include = array(), array $exclude = array())
    {
        if ($this->locked) {
            throw new BadMethodCallException('FieldSetBuilder methods cannot be accessed anymore once the builder is turned into a FieldSet instance.');
        }

        if (!$this->mappingReader) {
            throw new BadMethodCallException('FieldSetBuilder is unable to import configuration from class because no MappingReader is set.');
        }

        $metadata = $this->mappingReader->getMetadataForClass($class);
        foreach ($metadata->propertyMetadata as $property => $field) {
            /** @var PropertyMetadata $field */
            if (($include && !in_array($field->fieldName, $include)) xor ($exclude && in_array($field->fieldName, $exclude))) {
                continue;
            }

            $this->unresolvedFields[$field->fieldName] = array(
                'type' => $field->type,
                'options' => $field->options,
                'required' => $field->required,
                'class' => $class,
                'property' => $property
            );
        }

        return $this;
    }

    /**
     * @return FieldSet
     *
     * @throws BadMethodCallException
     */
    public function getFieldSet()
    {
        if ($this->locked) {
            throw new BadMethodCallException('FieldSetBuilder methods cannot be accessed anymore once the builder is turned into a FieldSet instance.');
        }

        foreach ($this->unresolvedFields as $name => $field) {
            if (!empty($field['class'])) {
                $this->fields[$name] = $this->searchFactory->createFieldForProperty($field['class'], $field['property'], $name, $field['type'], $field['options'], $field['required']);
            } else {
                $this->fields[$name] = $this->searchFactory->createField($name, $field['type'], $field['options'], $field['required']);
            }
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
