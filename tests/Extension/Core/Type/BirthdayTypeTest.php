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

use Rollerworks\Component\Search\Extension\Core\Type\BirthdayType;
use Rollerworks\Component\Search\Test\FieldTypeTestCase;

class BirthdayTypeTest extends FieldTypeTestCase
{
    public function testCreate()
    {
        $this->assertInstanceOf(
            'Rollerworks\Component\Search\FieldConfigInterface',
            $this->getFactory()->createField('birthday', BirthdayType::class)
        );
    }

    public function testDateOnlyInput()
    {
        $field = $this->getFactory()->createField('birthday', BirthdayType::class, [
            'format' => 'yyyy-MM-dd',
            'allow_age' => false,
        ]);

        $outputTime = new \DateTime('2010-06-02');
        $this->assertTransformedEquals($field, $outputTime, '2010-06-02', '2010-06-02');
        $this->assertTransformedFails($field, '21');
    }

    public function testAllowAgeInput()
    {
        $field = $this->getFactory()->createField('birthday', BirthdayType::class, [
            'format' => 'yyyy-MM-dd',
        ]);

        $this->assertTransformedEquals($field, 15, '15', '15');

        $outputTime = new \DateTime('2010-06-02');
        $this->assertTransformedEquals($field, $outputTime, '2010-06-02', '2010-06-02');
    }

    public function testWrongInputFails()
    {
        $field = $this->getFactory()->createField('birthday', BirthdayType::class, [
            'allow_age' => true,
        ]);

        $this->assertTransformedFails($field, 'twenty');
        $this->assertTransformedFails($field, '-21');
        $this->assertTransformedFails($field, '+21');
    }

    public function testAgeInTheFutureFails()
    {
        $field = $this->getFactory()->createField('birthday', BirthdayType::class, [
            'format' => 'yyyy-MM-dd',
        ]);

        $currentDate = new \DateTime('now + 1 day', new \DateTimeZone('UTC'));

        $this->assertTransformedFails($field, $currentDate->format('Y-m-d'));
    }

    public function testAgeInWorksWhenAllowed()
    {
        $field = $this->getFactory()->createField('birthday', BirthdayType::class, [
            'format' => 'yyyy-MM-dd',
            'allow_future_date' => true,
        ]);

        $currentDate = new \DateTime('now + 1 day', new \DateTimeZone('UTC'));
        $currentDate->setTime(0, 0, 0);

        $this->assertTransformedEquals($field, $currentDate, $currentDate->format('Y-m-d'), $currentDate->format('Y-m-d'));
    }

    protected function setUp()
    {
        parent::setUp();
    }

    protected function getTestedType()
    {
        return BirthdayType::class;
    }
}
