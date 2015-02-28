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

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface SearchExtensionInterface
{
    /**
     * Returns a type by name.
     *
     * @param string $name The name of the type
     *
     * @throws Exception\InvalidArgumentException if the given type is not supported by this extension
     *
     * @return FieldTypeInterface The type
     */
    public function getType($name);

    /**
     * Returns whether the given type is supported.
     *
     * @param string $name The name of the type
     *
     * @return bool Whether the type is supported by this extension
     */
    public function hasType($name);

    /**
     * Returns the extensions for the given type.
     *
     * @param string $name The name of the type
     *
     * @return FieldTypeExtensionInterface[] An array of extensions as FieldTypeExtensionInterface instances
     */
    public function getTypeExtensions($name);
}
