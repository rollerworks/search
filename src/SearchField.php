<?php

/*
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search;

use Rollerworks\Component\Search\Exception\BadMethodCallException;

/**
 * SearchField.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class SearchField implements FieldConfigInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var ResolvedFieldTypeInterface
     */
    private $type;

    /**
     * @var array
     */
    private $options;

    /**
     * @var bool[]
     */
    private $supportedValueTypes = array(
        ValuesBag::VALUE_TYPE_RANGE => false,
        ValuesBag::VALUE_TYPE_COMPARISON => false,
        ValuesBag::VALUE_TYPE_PATTERN_MATCH => false,
    );

    /**
     * @var bool
     */
    private $required = false;

    /**
     * @var ValueComparisonInterface
     */
    private $valueComparison;

    /**
     * @var bool
     */
    private $locked = false;

    /**
     * @var array
     */
    private $viewTransformers = array();

    /**
     * Constructor.
     *
     * @param string                     $name
     * @param ResolvedFieldTypeInterface $type
     * @param array                      $options
     *
     * @throws \InvalidArgumentException when the name is invalid.
     */
    public function __construct($name, ResolvedFieldTypeInterface $type, array $options = array())
    {
        FieldSet::validateName($name);

        if ('' === $name) {
            throw new \InvalidArgumentException(
                sprintf(
                    'The name "%s" contains illegal characters. Names should start with a letter, digit or underscore '.
                    'and only contain letters, digits, numbers, underscores ("_"), hyphens ("-") and colons (":").',
                    $name
                )
            );
        }

        $this->name = $name;
        $this->type = $type;
        $this->options = $options;
        $this->locked = false;
    }

    /**
     * @param bool $required
     *
     * @throws BadMethodCallException
     *
     * @return self
     */
    public function setRequired($required = true)
    {
        if ($this->locked) {
            throw new BadMethodCallException(
                'SearchField setter methods cannot be accessed anymore once the data is locked.'
            );
        }

        $this->required = $required;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function supportValueType($type)
    {
        if (!isset($this->supportedValueTypes[$type])) {
            throw new BadMethodCallException(
                sprintf(
                    'Unable to find configured-support for unknown value type "%s".',
                    $type
                )
            );
        }

        return $this->supportedValueTypes[$type];
    }

    /**
     * {@inheritdoc}
     *
     * @throws BadMethodCallException
     */
    public function setValueTypeSupport($type, $enabled)
    {
        if ($this->locked) {
            throw new BadMethodCallException(
                'SearchField setter methods cannot be accessed anymore once the data is locked.'
            );
        }

        if (!isset($this->supportedValueTypes[$type])) {
            throw new BadMethodCallException(
                sprintf(
                    'Unable to configure support for unknown value type "%s".',
                    $type
                )
            );
        }

        $this->supportedValueTypes[$type] = (bool) $enabled;

        return $this;
    }

    /**
     * Returns the name of field.
     *
     * @return string the Field name.
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the field types used to construct the field.
     *
     * @return ResolvedFieldTypeInterface The field's type.
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Returns whether the field is required to have values.
     *
     * @return bool
     */
    public function isRequired()
    {
        return $this->required;
    }

    /**
     * @param string $class
     * @param string $property
     *
     * @return self
     *
     * @throws BadMethodCallException when the data is locked
     *
     * @deprecated Deprecated since version 1.0.0-beta5, to be removed in 2.0.
     *             Use the 'model_class' and 'model_property' options instead.
     */
    public function setModelRef($class, $property)
    {
        if ($this->locked) {
            throw new BadMethodCallException(
                'SearchField setter methods cannot be accessed anymore once the data is locked.'
            );
        }

        $this->options['model_class'] = $class;
        $this->options['model_property'] = $property;

        return $this;
    }

    /**
     * Returns the Model's fully qualified class-name.
     *
     * This is required for certain storage engines.
     *
     * @return string|null
     */
    public function getModelRefClass()
    {
        return $this->getOption('model_class');
    }

    /**
     * Returns the Model field property-name.
     *
     * This is required for certain storage engines.
     *
     * @return string|null
     */
    public function getModelRefProperty()
    {
        return $this->getOption('model_property');
    }

    /**
     * Set the {@link ValueComparisonInterface} instance.
     *
     * @param ValueComparisonInterface $comparisonObj
     *
     * @return self
     *
     * @throws BadMethodCallException when the data is locked
     */
    public function setValueComparison(ValueComparisonInterface $comparisonObj)
    {
        if ($this->locked) {
            throw new BadMethodCallException(
                'SearchField setter methods cannot be accessed anymore once the data is locked.'
            );
        }

        $this->valueComparison = $comparisonObj;

        return $this;
    }

    /**
     * Returns the configured {@link ValueComparisonInterface} instance.
     *
     * @return ValueComparisonInterface
     */
    public function getValueComparison()
    {
        return $this->valueComparison;
    }

    /**
     * Appends / prepends a transformer to the view transformer chain.
     *
     * The transform method of the transformer is used to convert data from the
     * normalized to the view format.
     * The reverseTransform method of the transformer is used to convert from the
     * view to the normalized format.
     *
     * @param DataTransformerInterface $viewTransformer
     * @param bool                     $forcePrepend    if set to true, prepend instead of appending
     *
     * @return self The configuration object.
     *
     * @throws BadMethodCallException when the data is locked
     */
    public function addViewTransformer(DataTransformerInterface $viewTransformer, $forcePrepend = false)
    {
        if ($this->locked) {
            throw new BadMethodCallException(
                'SearchField setter methods cannot be accessed anymore once the data is locked.'
            );
        }

        if ($forcePrepend) {
            array_unshift($this->viewTransformers, $viewTransformer);
        } else {
            $this->viewTransformers[] = $viewTransformer;
        }

        return $this;
    }

    /**
     * Clears the view transformers.
     *
     * @return self The configuration object.
     *
     * @throws BadMethodCallException when the data is locked
     */
    public function resetViewTransformers()
    {
        if ($this->locked) {
            throw new BadMethodCallException(
                'SearchField setter methods cannot be accessed anymore once the data is locked.'
            );
        }

        $this->viewTransformers = array();

        return $this;
    }

    /**
     * Returns the view transformers of the field.
     *
     * @return DataTransformerInterface[] An array of {@link DataTransformerInterface} instances.
     */
    public function getViewTransformers()
    {
        return $this->viewTransformers;
    }

    /**
     * Sets the field's data is locked.
     *
     * After calling this method, setter methods can be no longer called.
     *
     * @param bool $locked
     *
     * @throws BadMethodCallException when the data is locked
     */
    public function setDataLocked($locked = true)
    {
        if ($this->locked) {
            throw new BadMethodCallException(
                'SearchField setter methods cannot be accessed anymore once the data is locked.'
            );
        }

        $this->locked = $locked;
    }

    /**
     * Returns whether the field's data is locked.
     *
     * A field with locked data is restricted to the data passed in
     * this configuration.
     *
     * @return bool Whether the data is locked.
     */
    public function getDataLocked()
    {
        return $this->locked;
    }

    /**
     * Returns all options passed during the construction of the field.
     *
     * @return array The passed options.
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Returns whether a specific option exists.
     *
     * @param string $name The option name,
     *
     * @return bool Whether the option exists.
     */
    public function hasOption($name)
    {
        return array_key_exists($name, $this->options);
    }

    /**
     * Returns the value of a specific option.
     *
     * @param string $name    The option name.
     * @param mixed  $default The value returned if the option does not exist.
     *
     * @return mixed The option value.
     */
    public function getOption($name, $default = null)
    {
        if (array_key_exists($name, $this->options)) {
            return $this->options[$name];
        }

        return $default;
    }
}
