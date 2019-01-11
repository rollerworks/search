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
use Rollerworks\Component\Search\FieldSetView;
use Rollerworks\Component\Search\ValueComparator;

/**
 * @author Dalibor Karlović <dalibor@flexolabs.io>
 */
final class OrderField implements FieldConfig
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
     * @var ValueComparator
     */
    private $valueComparator;

    /**
     * @var DataTransformer|null
     */
    private $viewTransformer;

    /**
     * @var DataTransformer|null
     */
    private $normTransformer;

    /**
     * @var array
     */
    private $attributes = [];

    /**
     * @throws \InvalidArgumentException When the name is invalid
     */
    public function __construct(string $name, ResolvedFieldType $type, array $options = [])
    {
        if (!preg_match('/^@_?[a-zA-Z][a-zA-Z0-9_\-]*$/D', $name)) {
            throw new InvalidArgumentException(
                sprintf(
                    'The name "%s" contains illegal characters. Name must start with @'.
                    'and only contain letters, digits, numbers, underscores ("_") and hyphens ("-").',
                    $name
                )
            );
        }

        $this->name = $name;
        $this->type = $type;
        $this->options = $options;
    }

    public static function isOrder(string $name): bool
    {
        return '@' === $name[0];
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @inheritdoc
     */
    public function getType(): ResolvedFieldType
    {
        return $this->type;
    }

    /**
     * @inheritdoc
     */
    public function supportValueType(string $type): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function setValueTypeSupport(string $type, bool $enabled): void
    {
        throw new BadMethodCallException(
            'OrderField does not support supporting custom value types'
        );
    }

    /**
     * @inheritdoc
     */
    public function setValueComparator(ValueComparator $comparator): void
    {
        throw new BadMethodCallException(
            'OrderField does not support supporting custom value comparator'
        );
    }

    /**
     * @inheritdoc
     */
    public function getValueComparator(): ?ValueComparator
    {
        return $this->valueComparator;
    }

    /**
     * @inheritdoc
     */
    public function setViewTransformer(?DataTransformer $transformer = null): void
    {
        $this->viewTransformer = $transformer;
    }

    /**
     * @inheritdoc
     */
    public function getViewTransformer(): ?DataTransformer
    {
        return $this->viewTransformer;
    }

    /**
     * @inheritdoc
     */
    public function setNormTransformer(?DataTransformer $transformer = null): void
    {
        $this->normTransformer = $transformer;
    }

    /**
     * @inheritdoc
     */
    public function getNormTransformer(): ?DataTransformer
    {
        return $this->normTransformer;
    }

    /**
     * @inheritdoc
     */
    public function isConfigLocked(): bool
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @inheritdoc
     */
    public function hasOption(string $name): bool
    {
        return array_key_exists($name, $this->options);
    }

    /**
     * @inheritdoc
     */
    public function getOption(string $name, $default = null)
    {
        if (array_key_exists($name, $this->options)) {
            return $this->options[$name];
        }

        return $default;
    }

    /**
     * @inheritdoc
     */
    public function createView(FieldSetView $fieldSet): SearchFieldView
    {
        $view = new SearchFieldView($fieldSet);

        $this->type->buildFieldView($view, $this, $this->options);

        return $view;
    }

    /**
     * @inheritdoc
     */
    public function setAttribute(string $name, $value)
    {
        $this->attributes[$name] = $value;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setAttributes(array $attributes)
    {
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @inheritdoc
     */
    public function hasAttribute(string $name): bool
    {
        return array_key_exists($name, $this->attributes);
    }

    /**
     * @inheritdoc
     */
    public function getAttribute(string $name, $default = null)
    {
        return array_key_exists($name, $this->attributes) ? $this->attributes[$name] : $default;
    }
}
