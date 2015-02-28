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
use Symfony\Component\Intl\Util\IntlTestHelper;

class IntegerTypeTest extends FieldTypeTestCase
{
    public function testCreate()
    {
        $this->getFactory()->createField('integer', 'integer');
    }

    public function testCastsToInteger()
    {
        $field = $this->getFactory()->createField('integer', 'integer');

        $this->assertTransformedEquals($field, 1, '1.678', '1');
        $this->assertTransformedEquals($field, 1, '1', '1');
        $this->assertTransformedEquals($field, -1, '-1', '-1');
    }

    public function testWrongInputFails()
    {
        $field = $this->getFactory()->createField('integer', 'integer');

        $this->assertTransformedFails($field, 'foo');
        $this->assertTransformedFails($field, '+1');
    }

    protected function setUp()
    {
        IntlTestHelper::requireIntl($this);

        parent::setUp();
    }

    protected function getTestedType()
    {
        return 'integer';
    }
}
