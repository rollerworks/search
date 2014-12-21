<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search\Tests\Extension\Core\Type;

use Rollerworks\Component\Search\Test\FieldTypeTestCase;

class TextTypeTest extends FieldTypeTestCase
{
    public function testCreate()
    {
        $this->factory->createField('name', 'text');
    }

    protected function getTestedType()
    {
        return 'text';
    }
}
