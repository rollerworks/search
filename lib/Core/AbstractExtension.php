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
use Rollerworks\Component\Search\Field\FieldType;
use Rollerworks\Component\Search\Field\FieldTypeExtension;

/**
 * The AbstractExtension can be used as a base class for SearchExtensions.
 *
 * An added bonus for extending this class rather then the implementing the the
 * {@link SearchExtensionInterface} is that any new methods added the
 * SearchExtensionInterface will not break existing implementations.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
abstract class AbstractExtension implements SearchExtension
{
    /** @var array<FieldTypeExtension[]>|null */
    private $typesExtensions;

    /** @var FieldType[]|null */
    private $types;

    public function getType(string $name): FieldType
    {
        if ($this->types === null) {
            $this->initTypes();
        }

        if (! isset($this->types[$name])) {
            throw new InvalidArgumentException(
                \sprintf('Type "%s" can not be loaded by this extension', $name)
            );
        }

        return $this->types[$name];
    }

    public function hasType(string $name): bool
    {
        if ($this->types === null) {
            $this->initTypes();
        }

        return isset($this->types[$name]);
    }

    public function hasTypeExtensions(string $type): bool
    {
        if ($this->typesExtensions === null) {
            $this->initTypesExtensions();
        }

        return isset($this->typesExtensions[$type]);
    }

    public function getTypeExtensions(string $type): array
    {
        if ($this->typesExtensions === null) {
            $this->initTypesExtensions();
        }

        return $this->typesExtensions[$type] ?? [];
    }

    /**
     * If extension needs to provide new field types this function
     * should be overloaded in child class and return an array of FieldType
     * instances.
     *
     * This is only required for types that have a constructor with (required) arguments.
     *
     * @return FieldType[]
     */
    protected function loadTypes(): array
    {
        return [];
    }

    /**
     * If extension needs to provide field type extensions this function
     * should be overloaded in child class and return array of FieldTypeExtension
     * instances per type: `TypeClassName => [FieldTypeExtensionInterface, ...]`.
     *
     * @return array|array<FieldTypeExtension[]>
     */
    protected function loadTypesExtensions(): array
    {
        return [];
    }

    private function initTypes(): void
    {
        $this->types = [];

        foreach ($this->loadTypes() as $type) {
            if (! $type instanceof FieldType) {
                throw new UnexpectedTypeException($type, FieldType::class);
            }

            $this->types[\get_class($type)] = $type;
        }
    }

    private function initTypesExtensions(): void
    {
        $this->typesExtensions = [];

        foreach ($this->loadTypesExtensions() as $extension) {
            if (! $extension instanceof FieldTypeExtension) {
                throw new UnexpectedTypeException($extension, FieldTypeExtension::class);
            }

            $this->typesExtensions[$extension->getExtendedType()][] = $extension;
        }
    }
}
