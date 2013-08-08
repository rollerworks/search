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

use Rollerworks\Component\Search\Exception\UnexpectedTypeException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

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

    public function __construct(FieldTypeInterface $innerType, array $typeExtensions = array(), ResolvedFieldTypeInterface $parent = null)
    {
        if (!preg_match('/^[a-z0-9_]*$/i', $innerType->getName())) {
            throw new \InvalidArgumentException(sprintf(
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
     * @return string The type name.
     */
    public function getName()
    {
        return $this->innerType->getName();
    }

    /**
     * Returns the parent type.
     *
     * @return ResolvedFieldTypeInterface|null The parent type or null.
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Returns the wrapped form type.
     *
     * @return FieldTypeInterface The wrapped form type.
     */
    public function getInnerType()
    {
        return $this->innerType;
    }

    /**
     * Returns the extensions of the wrapped form type.
     *
     * @return FieldTypeExtensionInterface[] An array of {@link FormTypeExtensionInterface} instances.
     */
    public function getTypeExtensions()
    {
        return $this->typeExtensions;
    }

    /**
     * Returns the configured options resolver used for this type.
     *
     * @return OptionsResolverInterface The options resolver.
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
}
