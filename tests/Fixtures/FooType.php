<?php

/**
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests\Fixtures;

use Rollerworks\Component\Search\AbstractFieldType;

class FooType extends AbstractFieldType
{
    public function getName()
    {
        return 'foo';
    }

    public function getParent()
    {
        return null;
    }
}
