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

use Rollerworks\Component\Search\Exception\InvalidArgumentException;
use Rollerworks\Component\Search\Exception\UnexpectedTypeException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class ResolvedFieldType implements ResolvedFieldTypeInterface
{
    /**
     * @var FieldTypeInterface
     */
    private $innerType;

    /**
     * @var FieldTypeExtensionInterface[]
     */
    private $typeExtensions;

    /**
     * @var ResolvedFieldType
     */
    private $parent;

    /**
     * @var OptionsResolver
     */
    private $optionsResolver;

    /**
     * Constructor.
     *
     * @param FieldTypeInterface         $innerType
     * @param array                      $typeExtensions
     * @param ResolvedFieldTypeInterface $parent
     *
     * @throws UnexpectedTypeException  When at least one of the given extensions is not an FieldTypeExtensionInterface
     * @throws InvalidArgumentException When the name of inner type is invalid
     */
    public function __construct(FieldTypeInterface $innerType, array $typeExtensions = [], ResolvedFieldTypeInterface $parent = null)
    {
        if (!preg_match('/^[a-z0-9_]*$/i', $innerType->getName())) {
            throw new InvalidArgumentException(
                sprintf(
                    'The "%s" field-type name ("%s") is not valid. Names must only contain letters, numbers, and "_".',
                    get_class($innerType),
                    $innerType->getName()
                )
            );
        }

        foreach ($typeExtensions as $extension) {
            if (!$extension instanceof FieldTypeExtensionInterface) {
                throw new UnexpectedTypeException(
                    $extension,
                    'Rollerworks\Component\Search\FieldTypeExtensionInterface'
                );
            }
        }

        $this->innerType = $innerType;
        $this->typeExtensions = $typeExtensions;
        $this->parent = $parent;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->innerType->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * {@inheritdoc}
     */
    public function getInnerType()
    {
        return $this->innerType;
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeExtensions()
    {
        return $this->typeExtensions;
    }

    /**
     * {@inheritdoc}
     */
    public function createField($name, array $options = [])
    {
        $options = $this->getOptionsResolver()->resolve($options);
        $builder = $this->newField($name, $options);

        return $builder;
    }

    /**
     * {@inheritdoc}
     */
    public function buildType(FieldConfigInterface $config, array $options)
    {
        if (null !== $this->parent) {
            $this->parent->buildType($config, $options);
        }

        $this->innerType->buildType($config, $options);

        foreach ($this->typeExtensions as $extension) {
            $extension->buildType($config, $options);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function createFieldView(FieldConfigInterface $config)
    {
        $view = $this->newFieldView($config);
        $view->vars = array_merge($view->vars, [
            'name' => $config->getName(),
            'type' => $config->getType()->getName(),
            'accept_ranges' => $config->supportValueType(ValuesBag::VALUE_TYPE_RANGE),
            'accept_compares' => $config->supportValueType(ValuesBag::VALUE_TYPE_COMPARISON),
            'accept_pattern_matchers' => $config->supportValueType(ValuesBag::VALUE_TYPE_PATTERN_MATCH),
        ]);

        return $view;
    }

    /**
     * {@inheritdoc}
     */
    public function buildFieldView(SearchFieldView $view, FieldConfigInterface $config, array $options)
    {
        if (null !== $this->parent) {
            $this->parent->buildFieldView($view, $config, $options);
        }

        $this->innerType->buildView($view, $config, $options);

        foreach ($this->typeExtensions as $extension) {
            $extension->buildView($config, $view);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getOptionsResolver()
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
     *
     * @param string $name    The name of the field
     * @param array  $options The builder options
     *
     * @return SearchField The new field instance
     */
    protected function newField($name, array $options)
    {
        return new SearchField($name, $this, $options);
    }

    /**
     * Creates a new SearchFieldView instance.
     *
     * Override this method if you want to customize the view class.
     *
     * @param FieldConfigInterface $config The search field
     *
     * @return SearchFieldView The new view instance
     */
    protected function newFieldView(FieldConfigInterface $config)
    {
        return new SearchFieldView($config);
    }
}
