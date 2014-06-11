<?php

/**
 * This file is part of RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Extension\Core\Type;

use Rollerworks\Component\Search\AbstractFieldType;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class TextType extends AbstractFieldType
{
    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'text';
    }

    /**
     * {@inheritDoc}
     */
    public function hasPatternMatchSupport()
    {
        return true;
    }
}
