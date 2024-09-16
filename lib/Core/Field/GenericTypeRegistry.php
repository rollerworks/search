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

use Rollerworks\Component\Search\Exception\InvalidArgumentException;
use Rollerworks\Component\Search\Exception\SearchException;
use Rollerworks\Component\Search\Exception\UnexpectedTypeException;
use Rollerworks\Component\Search\SearchExtension;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class GenericTypeRegistry implements TypeRegistry
{
    /**
     * @var SearchExtension[]
     */
    private $extensions = [];

    /**
     * @var ResolvedFieldType[]
     */
    private $types = [];

    private $resolvedTypeFactory;

    /**
     * @param SearchExtension[] $extensions
     *
     * @throws UnexpectedTypeException if an extension does not implement SearchExtension
     */
    public function __construct(array $extensions, ResolvedFieldTypeFactory $resolvedTypeFactory)
    {
        foreach ($extensions as $extension) {
            if (! $extension instanceof SearchExtension) {
                throw new UnexpectedTypeException($extension, SearchExtension::class);
            }
        }

        $this->extensions = $extensions;
        $this->resolvedTypeFactory = $resolvedTypeFactory;
    }

    public function getType(string $name): ResolvedFieldType
    {
        if (! isset($this->types[$name])) {
            $type = null;

            foreach ($this->extensions as $extension) {
                if ($extension->hasType($name)) {
                    $type = $extension->getType($name);

                    break;
                }
            }

            if (! $type) {
                // Support fully-qualified class names.
                if (! class_exists($name) || ! \in_array(FieldType::class, class_implements($name), true)) {
                    throw new InvalidArgumentException(\sprintf('Could not load type "%s"', $name));
                }

                $type = new $name();
            }

            $this->types[$name] = $this->resolveType($type);
        }

        return $this->types[$name];
    }

    public function hasType(string $name): bool
    {
        if (isset($this->types[$name])) {
            return true;
        }

        try {
            $this->getType($name);
        } catch (SearchException $e) {
            return false;
        }

        return true;
    }

    public function getExtensions(): array
    {
        return $this->extensions;
    }

    private function resolveType(FieldType $type): ResolvedFieldType
    {
        $parentType = $type->getParent();
        $fqcn = $type::class;

        $typeExtensions = [];

        foreach ($this->extensions as $extension) {
            $typeExtensions = array_merge(
                $typeExtensions,
                $extension->getTypeExtensions($fqcn)
            );
        }

        return $this->resolvedTypeFactory->createResolvedType(
            $type,
            $typeExtensions,
            $parentType ? $this->getType($parentType) : null
        );
    }
}
