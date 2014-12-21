<?php

/*
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests\Mocks;

use Rollerworks\Component\Search\DataTransformerInterface;
use Rollerworks\Component\Search\Extension\Core\Type\FieldType;
use Rollerworks\Component\Search\Extension\Core\Type\TextType;
use Rollerworks\Component\Search\Extension\Core\ValueComparison\SimpleValueComparison;
use Rollerworks\Component\Search\FieldConfigInterface;
use Rollerworks\Component\Search\ResolvedFieldType;
use Rollerworks\Component\Search\ResolvedFieldTypeInterface;
use Rollerworks\Component\Search\ValueComparisonInterface;

class FieldConfigMock implements FieldConfigInterface
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
     * @var boolean
     */
    private $acceptRanges = false;

    /**
     * @var boolean
     */
    private $acceptCompares = false;

    /**
     * @var boolean
     */
    private $acceptPatternMatch = false;

    /**
     * @var boolean
     */
    private $required = false;

    /**
     * @var ValueComparisonInterface
     */
    private $valueComparison;

    /**
     * @var string
     */
    private $modelRefClass;

    /**
     * @var string
     */
    private $modelRefField;

    /**
     * @var boolean
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
     */
    public function __construct($name, ResolvedFieldTypeInterface $type = null, array $options = array())
    {
        if (null === $type) {
            $type = new ResolvedFieldType(
                new TextType(),
                array(),
                new ResolvedFieldType(new FieldType(
                    new SimpleValueComparison()
                ))
            );
        }

        $this->name = $name;
        $this->type = $type;
        $this->options = $options;
        $this->locked = false;
    }

    /**
     * @param string                     $name
     * @param ResolvedFieldTypeInterface $type
     * @param array                      $options
     *
     * @return FieldConfigMock
     */
    public static function create($name, ResolvedFieldTypeInterface $type = null, array $options = array())
    {
        return new self($name, $type, $options);
    }

    /**
     * @param boolean $required
     *
     * @return $this
     */
    public function setRequired($required = true)
    {
        $this->required = $required;

        return $this;
    }

    /**
     * @param boolean $acceptRanges
     *
     * @return $this
     */
    public function setAcceptRange($acceptRanges = true)
    {
        $this->acceptRanges = $acceptRanges;

        return $this;
    }

    /**
     * @param boolean $acceptCompares
     *
     * @return $this
     */
    public function setAcceptCompares($acceptCompares = true)
    {
        $this->acceptCompares = $acceptCompares;

        return $this;
    }

    /**
     * @param boolean $acceptPatternMatch
     *
     * @return $this
     */
    public function setAcceptPatternMatch($acceptPatternMatch = true)
    {
        $this->acceptPatternMatch = $acceptPatternMatch;

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
     * Returns whether ranges are accepted.
     *
     * @return boolean
     */
    public function acceptRanges()
    {
        return $this->acceptRanges;
    }

    /**
     * Returns whether comparisons are accepted.
     *
     * @return boolean
     */
    public function acceptCompares()
    {
        return $this->acceptCompares;
    }

    /**
     * @return boolean
     */
    public function acceptPatternMatch()
    {
        return $this->acceptPatternMatch;
    }

    /**
     * Returns whether the field is required to have values.
     *
     * @return boolean
     */
    public function isRequired()
    {
        return $this->required;
    }

    /**
     * @param string $class
     * @param string $property
     *
     * @return $this
     */
    public function setModelRef($class, $property)
    {
        $this->modelRefClass = $class;
        $this->modelRefField = $property;

        return $this;
    }

    /**
     * Returns the Model's fully qualified class-name.
     *
     * This is required for certain storage engines.
     *
     * @return string
     */
    public function getModelRefClass()
    {
        return $this->modelRefClass;
    }

    /**
     * Returns the Model field property-name.
     *
     * This is required for certain storage engines.
     *
     * @return string
     */
    public function getModelRefProperty()
    {
        return $this->modelRefField;
    }

    /**
     * Set the {@link ValueComparisonInterface} instance.
     *
     * @param ValueComparisonInterface $comparisonObj
     *
     * @return $this
     */
    public function setValueComparison(ValueComparisonInterface $comparisonObj)
    {
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
     * @param Boolean                  $forcePrepend    if set to true, prepend instead of appending
     *
     * @return self The configuration object.
     */
    public function addViewTransformer(DataTransformerInterface $viewTransformer, $forcePrepend = false)
    {
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
     */
    public function resetViewTransformers()
    {
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
     * Returns whether the field's data is locked.
     *
     * A field with locked data is restricted to the data passed in
     * this configuration.
     *
     * @return Boolean Whether the data is locked.
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
     * @return Boolean Whether the option exists.
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
