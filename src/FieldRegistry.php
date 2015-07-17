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

use Rollerworks\Component\Search\Exception\ExceptionInterface;
use Rollerworks\Component\Search\Exception\InvalidArgumentException;
use Rollerworks\Component\Search\Exception\UnexpectedTypeException;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class FieldRegistry implements FieldRegistryInterface
{
    /**
     * Extensions.
     *
     * @var SearchExtensionInterface[]
     */
    private $extensions = [];

    /**
     * @var FieldTypeInterface[]
     */
    private $types = [];

    /**
     * @var ResolvedFieldTypeFactoryInterface
     */
    private $resolvedTypeFactory;

    /**
     * Constructor.
     *
     * @param SearchExtensionInterface[]        $extensions          An array of SearchExtensionInterface
     * @param ResolvedFieldTypeFactoryInterface $resolvedTypeFactory The factory for resolved field types
     *
     * @throws UnexpectedTypeException if any extension does not implement SearchExtensionInterface
     */
    public function __construct(array $extensions, ResolvedFieldTypeFactoryInterface $resolvedTypeFactory)
    {
        foreach ($extensions as $extension) {
            if (!$extension instanceof SearchExtensionInterface) {
                throw new UnexpectedTypeException($extension, 'Rollerworks\Component\Search\SearchExtensionInterface');
            }
        }

        $this->extensions = $extensions;
        $this->resolvedTypeFactory = $resolvedTypeFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function getType($name)
    {
        if (!is_string($name)) {
            throw new UnexpectedTypeException($name, 'string');
        }

        if (!isset($this->types[$name])) {
            $type = null;

            foreach ($this->extensions as $extension) {
                if ($extension->hasType($name)) {
                    $type = $extension->getType($name);

                    break;
                }
            }

            if (!$type) {
                throw new InvalidArgumentException(
                    sprintf('Could not load type "%s"', $name)
                );
            }

            $this->resolveAndAddType($type);
        }

        return $this->types[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function hasType($name)
    {
        if (isset($this->types[$name])) {
            return true;
        }

        try {
            $this->getType($name);
        } catch (ExceptionInterface $e) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensions()
    {
        return $this->extensions;
    }

    /**
     * Wraps a type into a ResolvedFieldTypeInterface implementation and connects
     * it with its parent type.
     *
     * @param FieldTypeInterface $type The type to resolve
     *
     * @return ResolvedFieldTypeInterface The resolved type
     */
    private function resolveAndAddType(FieldTypeInterface $type)
    {
        $parentType = $type->getParent();

        if ($parentType instanceof FieldTypeInterface) {
            $this->resolveAndAddType($parentType);

            $parentType = $parentType->getName();
        }

        $typeExtensions = [];

        foreach ($this->extensions as $extension) {
            $typeExtensions = array_merge(
                $typeExtensions,
                $extension->getTypeExtensions($type->getName())
            );
        }

        $this->types[$type->getName()] = $this->resolvedTypeFactory->createResolvedType(
            $type,
            $typeExtensions,
            $parentType ? $this->getType($parentType) : null
        );
    }
}
