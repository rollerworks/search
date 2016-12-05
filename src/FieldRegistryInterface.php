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

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface FieldRegistryInterface
{
    /**
     * Returns a field type by name.
     *
     * This methods registers the type extensions from the search extensions.
     *
     * @param string $name The name of the type
     *
     * @throws Exception\InvalidArgumentException if the type cannot be retrieved from any extension
     *
     * @return ResolvedFieldTypeInterface
     */
    public function getType(string $name): ResolvedFieldTypeInterface;

    /**
     * Returns whether the given field type is supported.
     *
     * @param string $name
     *
     * @return bool
     */
    public function hasType(string $name): bool;

    /**
     * Returns the extensions loaded on the registry.
     *
     * @return SearchExtensionInterface[]
     */
    public function getExtensions(): array;
}
