<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
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
     * Attempts to read the search fields configuration
     * from the class metadata.
     *
     * @param string $class The class name to look in (FQCN)
     *
     * @return SearchField[] An associative array with search fields
     */
    public function getSearchFields($class);

    /**
     * Attempts to read the field metadata of a specified property.
     *
     * @param string $class The class name to test (FQCN)
     * @param string $field The search-field name
     *
     * @return SearchField|null The SearchField or null when not found
     */
    public function getSearchField($class, $field);
}
