<?php

/**
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Metadata;

/**
 * MetadataReaderInterface must be implemented by a MetadataReader.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface MetadataReaderInterface
{
    /**
     * Attempts to read the search fields from a class.
     *
     * @param string $class The class name to test (FQCN).
     *
     * @return SearchField[]|array An associative array with search fields
     */
    public function getSearchFields($class);

    /**
     * Attempts to read the mapping of a specified property.
     *
     * @param string $class The class name to test (FQCN).
     * @param string $field The field
     *
     * @return SearchField|null The field mapping, null when not found
     */
    public function getSearchField($class, $field);
}
