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
use Rollerworks\Component\Search\Test\FieldTransformationAssertion;
use Rollerworks\Component\Search\Test\SearchIntegrationTestCase;

class IntegerTypeTest extends SearchIntegrationTestCase
{
    public function testCreate()
    {
        $this->getFactory()->createField('integer', IntegerType::class);
    }

    public function testCastsToInteger()
    {
        $field = $this->getFactory()->createField('integer', IntegerType::class);

        FieldTransformationAssertion::assertThat($field)
            ->withInput('1.678')
            ->successfullyTransformsTo(1)
            ->andReverseTransformsTo('1');

        FieldTransformationAssertion::assertThat($field)
            ->withInput('1')
            ->successfullyTransformsTo(1)
            ->andReverseTransformsTo('1');

        FieldTransformationAssertion::assertThat($field)
            ->withInput('-1')
            ->successfullyTransformsTo(-1)
            ->andReverseTransformsTo('-1');
    }

    public function testWrongInputFails()
    {
        $field = $this->getFactory()->createField('integer', IntegerType::class);

        FieldTransformationAssertion::assertThat($field)->withInput('foo')->failsToTransforms();
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

        self::assertArrayHasKey('precision', $fieldView->vars);
        self::assertArrayHasKey('grouping', $fieldView->vars);

        self::assertEquals(2, $fieldView->vars['precision']);
        self::assertFalse($fieldView->vars['grouping']);
    }
}
