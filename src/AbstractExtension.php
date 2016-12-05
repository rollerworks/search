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

use Rollerworks\Component\Search\Exception\InvalidArgumentException;
use Rollerworks\Component\Search\Exception\UnexpectedTypeException;

/**
 * The AbstractExtension can be used as a base class for SearchExtensions.
 *
 * An added bonus for extending this class rather then the implementing the the
 * {@link SearchExtensionInterface} is that any new methods added the
 * SearchExtensionInterface will not break existing implementations.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
abstract class AbstractExtension implements SearchExtensionInterface
{
    private $typesExtensions;
    private $types;

    /**
     * {@inheritdoc}
     */
    public function getType(string $name): FieldTypeInterface
    {
        if (null === $this->types) {
            $this->initTypes();
        }

        if (!isset($this->types[$name])) {
            throw new InvalidArgumentException(
                sprintf('Type "%s" can not be loaded by this extension', $name)
            );
        }

        return $this->types[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function hasType(string $name): bool
    {
        if (null === $this->types) {
            $this->initTypes();
        }

        return isset($this->types[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function hasTypeExtensions(string $type): bool
    {
        if (null === $this->typesExtensions) {
            $this->initTypesExtensions();
        }

        return isset($this->typesExtensions[$type]);
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeExtensions(string $type): array
    {
        if (null === $this->typesExtensions) {
            $this->initTypesExtensions();
        }

        return $this->typesExtensions[$type] ?? [];
    }

    /**
     * If extension needs to provide new field types this function
     * should be overloaded in child class and return an array of FieldTypeInterface
     * instances.
     *
     * This is only required for types that have a constructor with (required) arguments.
     *
     * @return FieldTypeInterface[]
     */
    protected function loadTypes(): array
    {
        return [];
    }

    /**
     * If extension needs to provide field type extensions this function
     * should be overloaded in child class and return array of FieldTypeExtensionInterface
     * instances per type: `TypeClassName => [FieldTypeExtensionInterface, ...]`.
     *
     * @return array
     */
    protected function loadTypesExtensions(): array
    {
        return [];
    }

    private function initTypes()
    {
        $this->types = [];

        foreach ($this->loadTypes() as $type) {
            if (!$type instanceof FieldTypeInterface) {
                throw new UnexpectedTypeException($type, FieldTypeInterface::class);
            }

            $this->types[get_class($type)] = $type;
        }
    }

    private function initTypesExtensions()
    {
        $this->typesExtensions = [];

        foreach ($this->loadTypesExtensions() as $extension) {
            if (!$extension instanceof FieldTypeExtensionInterface) {
                throw new UnexpectedTypeException($extension, FieldTypeExtensionInterface::class);
            }

            $this->typesExtensions[$extension->getExtendedType()][] = $extension;
        }
    }
}
