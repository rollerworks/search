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

use Rollerworks\Component\Search\Exception\UnexpectedTypeException;
use Rollerworks\Component\Search\FieldSetView;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class GenericResolvedFieldType implements ResolvedFieldType
{
    /** @var FieldType */
    private $innerType;

    /** @var FieldTypeExtension[] */
    private $typeExtensions;

    /** @var ResolvedFieldType|null */
    private $parent;

    /** @var OptionsResolver */
    private $optionsResolver;

    /**
     * @throws UnexpectedTypeException When at least one of the given extensions is not an FieldTypeExtension
     */
    public function __construct(FieldType $innerType, array $typeExtensions = [], ?ResolvedFieldType $parent = null)
    {
        foreach ($typeExtensions as $extension) {
            if (!$extension instanceof FieldTypeExtension) {
                throw new UnexpectedTypeException($extension, FieldTypeExtension::class);
            }
        }

        $this->innerType = $innerType;
        $this->typeExtensions = $typeExtensions;
        $this->parent = $parent;
    }

    public function getParent(): ?ResolvedFieldType
    {
        return $this->parent;
    }

    public function getInnerType(): FieldType
    {
        return $this->innerType;
    }

    public function getTypeExtensions(): array
    {
        return $this->typeExtensions;
    }

    public function createField(string $name, array $options = []): FieldConfig
    {
        $options = $this->getOptionsResolver()->resolve($options);

        return $this->newField($name, $options);
    }

    public function buildType(FieldConfig $config, array $options): void
    {
        if (null !== $this->parent) {
            $this->parent->buildType($config, $options);
        }

        $this->innerType->buildType($config, $options);

        foreach ($this->typeExtensions as $extension) {
            $extension->buildType($config, $options);
        }
    }

    public function createFieldView(FieldConfig $config, FieldSetView $view): SearchFieldView
    {
        $view = $this->newView($view);
        $view->vars = array_merge($view->vars, [
            'name' => $config->getName(),
            'accept_ranges' => $config->supportValueType(Range::class),
            'accept_compares' => $config->supportValueType(Compare::class),
            'accept_pattern_matchers' => $config->supportValueType(PatternMatch::class),
        ]);

        return $view;
    }

    public function buildFieldView(SearchFieldView $view, FieldConfig $config, array $options): void
    {
        if (null !== $this->parent) {
            $this->parent->buildFieldView($view, $config, $options);
        }

        $this->innerType->buildView($view, $config, $options);

        foreach ($this->typeExtensions as $extension) {
            $extension->buildView($config, $view);
        }
    }

    public function getBlockPrefix(): string
    {
        return $this->innerType->getBlockPrefix();
    }

    public function getOptionsResolver(): OptionsResolver
    {
        if (null === $this->optionsResolver) {
            if (null !== $this->parent) {
                $this->optionsResolver = clone $this->parent->getOptionsResolver();
            } else {
                $this->optionsResolver = new OptionsResolver();
            }

            $this->innerType->configureOptions($this->optionsResolver);

            foreach ($this->typeExtensions as $extension) {
                $extension->configureOptions($this->optionsResolver);
            }
        }

        return $this->optionsResolver;
    }

    /**
     * Creates a new SearchField instance.
     *
     * Override this method if you want to customize the field class.
     */
    protected function newField($name, array $options): FieldConfig
    {
        return new SearchField($name, $this, $options);
    }

    /**
     * Creates a new SearchFieldView instance.
     *
     * Override this method if you want to customize the view class.
     */
    protected function newView(FieldSetView $view): SearchFieldView
    {
        return new SearchFieldView($view);
    }
}
