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

use Rollerworks\Component\Search\Extension\Core\Type\IntegerType;
use Rollerworks\Component\Search\Test\FieldTypeTestCase;

class IntegerTypeTest extends FieldTypeTestCase
{
    public function testCreate()
    {
        $this->getFactory()->createField('integer', IntegerType::class);
    }

    public function testCastsToInteger()
    {
        $field = $this->getFactory()->createField('integer', IntegerType::class);

        $this->assertTransformedEquals($field, 1, '1.678', '1');
        $this->assertTransformedEquals($field, 1, '1', '1');
        $this->assertTransformedEquals($field, -1, '-1', '-1');
    }

    public function testWrongInputFails()
    {
        $field = $this->getFactory()->createField('integer', IntegerType::class);

        $this->assertTransformedFails($field, 'foo');
        $this->assertTransformedFails($field, '+1');
    }

    public function testViewIsConfiguredProperly()
    {
        $field = $this->getFactory()->createField(
            'integer',
            IntegerType::class,
            [
                'precision' => 2,
                'grouping' => false,
            ]
        );

        $field->setDataLocked();
        $fieldView = $field->createView();

        $this->assertArrayHasKey('precision', $fieldView->vars);
        $this->assertArrayHasKey('grouping', $fieldView->vars);

        $this->assertEquals(2, $fieldView->vars['precision']);
        $this->assertFalse($fieldView->vars['grouping']);
    }

    protected function setUp()
    {
        parent::setUp();
    }

    protected function getTestedType()
    {
        return 'integer';
    }
}
