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
use Rollerworks\Component\Search\SearchExtension;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface TypeRegistry
{
    /**
     * Returns a field type by name.
     *
     * @throws InvalidArgumentException if the type cannot be retrieved from any extension
     */
    public function getType(string $name): ResolvedFieldType;

    /**
     * Returns whether the given field type is supported.
     */
    public function hasType(string $name): bool;

    /**
     * Returns the extensions loaded on the registry.
     *
     * @return SearchExtension[]
     */
    public function getExtensions(): array;
}
