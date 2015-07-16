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
 * NullMetadataReader always returns null for any metadata.
 */
class NullMetadataReader implements MetadataReaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getSearchFields($class)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchField($class, $field)
    {
        // noop
    }
}
