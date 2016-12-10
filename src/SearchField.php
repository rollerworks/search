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

use Rollerworks\Component\Search\Exception\BadMethodCallException;
use Rollerworks\Component\Search\Exception\InvalidConfigurationException;
use Rollerworks\Component\Search\Value\RequiresComparatorValueHolder;

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
    private $supportedValueTypes = [];

    /**
     * @var ValueComparisonInterface
     */
    private $valueComparison;

    /**
     * @var bool
     */
    private $locked = false;

    /**
     * @var DataTransformerInterface|null
     */
    private $viewTransformer;

    /**
     * @var DataTransformerInterface|null
     */
    private $normTransformer;

    /**
     * Constructor.
     *
     * @param string                     $name
     * @param ResolvedFieldTypeInterface $type
     * @param array                      $options
     *
     * @throws \InvalidArgumentException When the name is invalid
     */
    public function __construct($name, ResolvedFieldTypeInterface $type, array $options = [])
    {
        if ('' === $name || !preg_match('/^[a-zA-Z][a-zA-Z0-9_\-]*$/D', $name)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'The name "%s" contains illegal characters. Name must start with a letter '.
                    'and only contain letters, digits, numbers, underscores ("_") and hyphens ("-").',
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
     * {@inheritdoc}
     */
    public function supportValueType(string $type): bool
    {
        return $this->supportedValueTypes[$type] ?? false;
    }

    /**
     * {@inheritdoc}
     *
     * @throws BadMethodCallException
     */
    public function setValueTypeSupport(string $type, bool $enabled)
    {
        if ($this->locked) {
            throw new BadMethodCallException(
                'SearchField setter methods cannot be accessed anymore once the data is locked.'
            );
        }

        $this->supportedValueTypes[$type] = $enabled;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * {@inheritdoc}
     *
     * @throws BadMethodCallException when the data is locked
     *
     * @return self
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
     * {@inheritdoc}
     */
    public function getValueComparison()
    {
        return $this->valueComparison;
    }

    /**
     * {@inheritdoc}
     */
    public function setViewTransformer(DataTransformerInterface $viewTransformer = null)
    {
        if ($this->locked) {
            throw new BadMethodCallException(
                'SearchField setter methods cannot be accessed anymore once the data is locked.'
            );
        }

        $this->viewTransformer = $viewTransformer;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getViewTransformer()
    {
        return $this->viewTransformer;
    }

    /**
     * {@inheritdoc}
     */
    public function setNormTransformer(DataTransformerInterface $viewTransformer = null)
    {
        if ($this->locked) {
            throw new BadMethodCallException(
                'SearchField setter methods cannot be accessed anymore once the data is locked.'
            );
        }

        $this->normTransformer = $viewTransformer;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getNormTransformer()
    {
        return $this->normTransformer;
    }

    /**
     * Finalize the configuration and mark config as locked.
     *
     * After calling this method, setter methods can be no longer called.
     *
     * @throws InvalidConfigurationException when a supported value-type requires
     *                                       a value comparator but none is set
     */
    public function finalizeConfig()
    {
        if ($this->locked) {
            return;
        }

        if (null === $this->valueComparison) {
            foreach ($this->supportedValueTypes as $type => $supported) {
                if ($supported && isset(class_implements($type)[RequiresComparatorValueHolder::class])) {
                    throw new InvalidConfigurationException(
                        sprintf(
                            'Supported value-type "%s" requires a value comparator but none is set for field "%s" with type "%s".',
                            $type,
                            $this->getName(),
                            get_class($this->getType()->getInnerType())
                        )
                    );
                }
            }
        }

        $this->locked = true;
    }

    /**
     * {@inheritdoc}
     */
    public function isConfigLocked()
    {
        return $this->locked;
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * {@inheritdoc}
     */
    public function hasOption($name)
    {
        return array_key_exists($name, $this->options);
    }

    /**
     * {@inheritdoc}
     */
    public function getOption($name, $default = null)
    {
        if (array_key_exists($name, $this->options)) {
            return $this->options[$name];
        }

        return $default;
    }

    /**
     * {@inheritdoc}
     */
    public function createView()
    {
        if (!$this->locked) {
            throw new BadMethodCallException(
                'Unable to create SearchFieldView when configuration is not locked.'
            );
        }

        $view = new SearchFieldView();

        $this->type->buildFieldView($view, $this, $this->options);

        return $view;
    }
}
