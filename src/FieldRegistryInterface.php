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
interface FieldRegistryInterface
{
    /**
     * Returns a field type by name.
     *
     * This methods registers the type extensions from the search extensions.
     *
     * @param string $name The name of the type
     *
     * @return ResolvedFieldTypeInterface The type
     *
     * @throws Exception\UnexpectedTypeException  if the passed name is not a string
     * @throws Exception\InvalidArgumentException if the type can not be retrieved from any extension
     */
    public function getType($name);

    /**
     * Returns whether the given field type is supported.
     *
     * @param string $name The name of the type
     *
     * @return Boolean Whether the type is supported
     */
    public function hasType($name);

    /**
     * Returns the extensions loaded by the framework.
     *
     * @return SearchExtensionInterface[]
     */
    public function getExtensions();
}
