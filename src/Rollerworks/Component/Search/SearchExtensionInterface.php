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
     * @return FieldTypeInterface The type
     *
     * @throws Exception\InvalidArgumentException if the given type is not supported by this extension
     */
    public function getType($name);

    /**
     * Returns whether the given type is supported.
     *
     * @param string $name The name of the type
     *
     * @return Boolean Whether the type is supported by this extension
     */
    public function hasType($name);

    /**
     * Returns the extensions for the given type.
     *
     * @param string $name The name of the type
     *
     * @return FieldTypeExtensionInterface[] An array of extensions as FormTypeExtensionInterface instances
     */
    public function getTypeExtensions($name);
}
