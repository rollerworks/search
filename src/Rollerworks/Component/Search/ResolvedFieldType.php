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

use Rollerworks\Component\Search\Exception\InvalidArgumentException;
use Rollerworks\Component\Search\Exception\UnexpectedTypeException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

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
     * @throws UnexpectedTypeException   When at least one of the given extensions is not an FieldTypeExtensionInterface.
     * @throws InvalidArgumentException  When the Inner Fieldname is invalid.
     */
    public function __construct(FieldTypeInterface $innerType, array $typeExtensions = array(), ResolvedFieldTypeInterface $parent = null)
    {
        if (!preg_match('/^[a-z0-9_]*$/i', $innerType->getName())) {
            throw new InvalidArgumentException(sprintf(
                'The "%s" field type name ("%s") is not valid. Names must only contain letters, numbers, and "_".',
                get_class($innerType),
                $innerType->getName()
            ));
        }

        foreach ($typeExtensions as $extension) {
            if (!$extension instanceof FieldTypeExtensionInterface) {
                throw new UnexpectedTypeException($extension, 'Rollerworks\Component\Search\FieldTypeExtensionInterface');
            }
        }

        $this->innerType = $innerType;
        $this->typeExtensions = $typeExtensions;
        $this->parent = $parent;
    }

    /**
     * Returns the name of the type.
     *
     * @return string The type name
     */
    public function getName()
    {
        return $this->innerType->getName();
    }

    /**
     * Returns the parent type.
     *
     * @return ResolvedFieldTypeInterface|null The parent type or null
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Returns the wrapped form type.
     *
     * @return FieldTypeInterface The wrapped form type
     */
    public function getInnerType()
    {
        return $this->innerType;
    }

    /**
     * Returns the extensions of the wrapped form type.
     *
     * @return FieldTypeExtensionInterface[] An array of {@link FieldTypeExtensionInterface} instances.
     */
    public function getTypeExtensions()
    {
        return $this->typeExtensions;
    }

    /**
     * {@inheritdoc}
     */
    public function createField($name, array $options = array())
    {
        $options = $this->getOptionsResolver()->resolve($options);
        $builder = $this->newField($name, $options);

        return $builder;
    }

    /**
     * This configures the {@link FieldConfigInterface}.
     *
     * This method is called for each type in the hierarchy starting from the
     * top most type. Type extensions can further modify the field.
     *
     * @param FieldConfigInterface $config
     * @param array                $options
     */
    public function buildType(FieldConfigInterface $config, array $options)
    {
        if (null !== $this->parent) {
            $this->parent->buildType($config, $options);
        }

        $this->innerType->buildType($config, $options);

        foreach ($this->typeExtensions as $extension) {
            /* @var FieldTypeExtensionInterface $extension */
            $extension->buildType($config, $options);
        }
    }

    /**
     * Returns the configured options resolver used for this type.
     *
     * @return OptionsResolverInterface The options resolver
     */
    public function getOptionsResolver()
    {
        if (null === $this->optionsResolver) {
            if (null !== $this->parent) {
                $this->optionsResolver = clone $this->parent->getOptionsResolver();
            } else {
                $this->optionsResolver = new OptionsResolver();
            }

            $this->innerType->setDefaultOptions($this->optionsResolver);

            foreach ($this->typeExtensions as $extension) {
                $extension->setDefaultOptions($this->optionsResolver);
            }
        }

        return $this->optionsResolver;
    }

    /**
     * Creates a new field instance.
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
}
