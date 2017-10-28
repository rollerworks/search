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

namespace Rollerworks\Component\Search\Field;

use Rollerworks\Component\Search\DataTransformer;
use Rollerworks\Component\Search\Exception\BadMethodCallException;
use Rollerworks\Component\Search\Exception\InvalidArgumentException;
use Rollerworks\Component\Search\Exception\InvalidConfigurationException;
use Rollerworks\Component\Search\FieldSetView;
use Rollerworks\Component\Search\Value\RequiresComparatorValueHolder;
use Rollerworks\Component\Search\ValueComparator;

/**
 * SearchField.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class SearchField implements FieldConfig
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var ResolvedFieldType
     */
    private $type;

    /**
     * @var array
     */
    private $options;

    /**
     * @var array
     */
    private $attributes = [];

    /**
     * @var bool[]
     */
    private $supportedValueTypes = [];

    /**
     * @var ValueComparator
     */
    private $valueComparator;

    /**
     * @var bool
     */
    private $locked = false;

    /**
     * @var DataTransformer|null
     */
    private $viewTransformer;

    /**
     * @var DataTransformer|null
     */
    private $normTransformer;

    /**
     * Constructor.
     *
     * @param string            $name
     * @param ResolvedFieldType $type
     * @param array             $options
     *
     * @throws \InvalidArgumentException When the name is invalid
     */
    public function __construct(string $name, ResolvedFieldType $type, array $options = [])
    {
        if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_\-]*$/D', $name)) {
            throw new InvalidArgumentException(
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
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getType(): ResolvedFieldType
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
    public function setValueComparator(ValueComparator $comparator)
    {
        if ($this->locked) {
            throw new BadMethodCallException(
                'SearchField setter methods cannot be accessed anymore once the data is locked.'
            );
        }

        $this->valueComparator = $comparator;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getValueComparator(): ?ValueComparator
    {
        return $this->valueComparator;
    }

    /**
     * {@inheritdoc}
     */
    public function setViewTransformer(DataTransformer $viewTransformer = null)
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
    public function getViewTransformer(): ?DataTransformer
    {
        return $this->viewTransformer;
    }

    /**
     * {@inheritdoc}
     */
    public function setNormTransformer(DataTransformer $viewTransformer = null)
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
    public function getNormTransformer(): ?DataTransformer
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
    public function finalizeConfig(): void
    {
        if ($this->locked) {
            return;
        }

        if (null === $this->valueComparator) {
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
    public function isConfigLocked(): bool
    {
        return $this->locked;
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * {@inheritdoc}
     */
    public function hasOption(string $name): bool
    {
        return array_key_exists($name, $this->options);
    }

    /**
     * {@inheritdoc}
     */
    public function getOption(string $name, $default = null)
    {
        if (array_key_exists($name, $this->options)) {
            return $this->options[$name];
        }

        return $default;
    }

    /**
     * {@inheritdoc}
     */
    public function createView(FieldSetView $fieldSet): SearchFieldView
    {
        if (!$this->locked) {
            throw new BadMethodCallException(
                'Unable to create SearchFieldView when configuration is not locked.'
            );
        }

        $view = new SearchFieldView($fieldSet);

        $this->type->buildFieldView($view, $this, $this->options);

        return $view;
    }

    /**
     * {@inheritdoc}
     */
    public function setAttribute(string $name, $value)
    {
        if ($this->locked) {
            throw new BadMethodCallException(
                'SearchField setter methods cannot be accessed anymore once the data is locked.'
            );
        }

        $this->attributes[$name] = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setAttributes(array $attributes)
    {
        if ($this->locked) {
            throw new BadMethodCallException(
                'SearchField setter methods cannot be accessed anymore once the data is locked.'
            );
        }

        $this->attributes = $attributes;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * {@inheritdoc}
     */
    public function hasAttribute(string $name): bool
    {
        return array_key_exists($name, $this->attributes);
    }

    /**
     * {@inheritdoc}
     */
    public function getAttribute(string $name, $default = null)
    {
        return array_key_exists($name, $this->attributes) ? $this->attributes[$name] : $default;
    }
}
