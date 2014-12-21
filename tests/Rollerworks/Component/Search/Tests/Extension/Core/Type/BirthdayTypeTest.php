<?php

/*
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests\Extension\Core\Type;

use Rollerworks\Component\Search\Test\FieldTypeTestCase;
use Symfony\Component\Intl\Util\IntlTestHelper;

class BirthdayTypeTest extends FieldTypeTestCase
{
    public function testCreate()
    {
        $this->factory->createField('birthday', 'birthday');
    }

    public function testDateOnlyInput()
    {
        $field = $this->factory->createField('birthday', 'birthday', array(
            'format' => 'yyyy-MM-dd',
            'allow_age' => false,
        ));

        $outputTime = new \DateTime('2010-06-02');
        $this->assertTransformedEquals($field, $outputTime, '2010-06-02', '2010-06-02');
        $this->assertTransformedFails($field, '21');
    }

    public function testAllowAgeInput()
    {
        $field = $this->factory->createField('birthday', 'birthday', array(
            'format' => 'yyyy-MM-dd',
        ));

        $this->assertTransformedEquals($field, 15, '15', '15');

        $outputTime = new \DateTime('2010-06-02');
        $this->assertTransformedEquals($field, $outputTime, '2010-06-02', '2010-06-02');
    }

    public function testWrongInputFails()
    {
        $field = $this->factory->createField('birthday', 'birthday', array(
            'allow_age' => true,
        ));

        $this->assertTransformedFails($field, 'twenty');
        $this->assertTransformedFails($field, '-21');
        $this->assertTransformedFails($field, '+21');
    }

    protected function setUp()
    {
        IntlTestHelper::requireIntl($this);

        parent::setUp();
    }

    protected function getTestedType()
    {
        return 'birthday';
    }
}
