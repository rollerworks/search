<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests\Extension\Core\Type;

use Rollerworks\Component\Search\Test\FieldTypeTestCase;

class TextTypeTest extends FieldTypeTestCase
{
    public function testCreate()
    {
        $this->getFactory()->createField('name', 'text');
    }

    protected function getTestedType()
    {
        return 'text';
    }
}
