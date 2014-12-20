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
        $this->assertTransformedEquals($field, $outputTime, '2010-06-02');
        $this->assertTransformedFails($field, '21');
    }

    public function testAllowAgeInput()
    {
        $field = $this->factory->createField('birthday', 'birthday', array(
            'format' => 'yyyy-MM-dd',
        ));

        $this->assertTransformedEquals($field, 15, '15');

        $outputTime = new \DateTime('2010-06-02');
        $this->assertTransformedEquals($field, $outputTime, '2010-06-02');
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
