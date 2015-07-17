<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Mapping;

use Rollerworks\Component\Search\Metadata\Field as MetadataField;

/**
 * Annotation class for search fields.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * @Annotation
 * @Target({"PROPERTY"})
 *
 * @deprecated This class is deprecated since 1.0beta5 and will be removed 2.0,
 *             use `Rollerworks\Component\Search\Metadata\SearchField` instead
 */
class Field extends MetadataField
{
}
