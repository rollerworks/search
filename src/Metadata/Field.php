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

use Rollerworks\Component\Search\Mapping\Field as MappingSearchField;

/**
 * Annotation class for search fields.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * @Annotation
 * @Target({"PROPERTY"})
 *
 * @deprecated This class is deprecated since 1.0beta3 and will be removed 2.0,
 *             use `Rollerworks\Component\Search\Mapping\SearchField` instead
 */
class Field extends MappingSearchField
{
}
