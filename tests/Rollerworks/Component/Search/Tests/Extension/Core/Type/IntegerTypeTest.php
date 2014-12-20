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

use Rollerworks\Component\Search\Extension\Core\Type\DateTimeType;
use Rollerworks\Component\Search\Test\FieldTypeTestCase;
use Symfony\Component\Intl\Util\IntlTestHelper;

class IntegerTypeTest extends FieldTypeTestCase
{
    public function testCreate()
    {
        $this->factory->createField('integer', 'integer');
    }

    public function testCastsToInteger()
    {
        $field = $this->factory->createField('integer', 'integer');

        $this->assertTransformedEquals($field, 1, '1.678', '1');
        $this->assertTransformedEquals($field, 1, '1', '1');
        $this->assertTransformedEquals($field, -1, '-1', '-1');
    }

    public function testWrongInputFails()
    {
        $field = $this->factory->createField('integer', 'integer');

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
