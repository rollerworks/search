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

use Rollerworks\Component\Search\Extension\Core\ChoiceList\ObjectChoiceList;
use Rollerworks\Component\Search\Test\FieldTypeTestCase;

class ChoiceTypeTest extends FieldTypeTestCase
{
    private $choices = [
        'a' => 'Bernhard',
        'b' => 'Fabien',
        'c' => 'Kris',
        'd' => 'Jon',
        'e' => 'Roman',
    ];

    private $numericChoices = [
        0 => 'Bernhard',
        1 => 'Fabien',
        2 => 'Kris',
        3 => 'Jon',
        4 => 'Roman',
    ];

    private $objectChoices;

    protected function setUp()
    {
        parent::setUp();

        $this->objectChoices = [
            (object) ['id' => 1, 'name' => 'Bernhard'],
            (object) ['id' => 2, 'name' => 'Fabien'],
            (object) ['id' => 3, 'name' => 'Kris'],
            (object) ['id' => 4, 'name' => 'Jon'],
            (object) ['id' => 5, 'name' => 'Roman'],
        ];
    }

    protected function tearDown()
    {
        parent::tearDown();

        $this->objectChoices = null;
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     */
    public function testChoiceListOptionExpectsChoiceListInterface()
    {
        $this->getFactory()->createField('choice', 'choice', [
            'choice_list' => ['foo' => 'foo'],
        ]);
    }

    public function testChoiceListAndChoicesCanBeEmpty()
    {
        $this->getFactory()->createField('choice', 'choice');
    }

    public function testSubmitSingleNonExpandedInvalidChoice()
    {
        $this->getFactory()->createField('choice', 'choice', [
            'choices' => $this->choices,
        ]);
    }

    public function testObjectChoices()
    {
        $field = $this->getFactory()->createField('choice', 'choice', [
            'choice_list' => new ObjectChoiceList(
                $this->objectChoices,
                // label path
                'name',
                // value path
                'id'
            ),
        ]);

        $this->assertTransformedEquals(
            $field,
            $this->objectChoices[2],
            $this->objectChoices[2]->id,
            $this->objectChoices[2]->id
        );
    }

    public function testObjectChoicesByLabel()
    {
        $field = $this->getFactory()->createField('choice', 'choice', [
            'label_as_value' => true,
            'choice_list' => new ObjectChoiceList(
                $this->objectChoices,
                // label path
                'name',
                // value path
                'id'
            ),
        ]);

        $this->assertTransformedEquals(
            $field,
            $this->objectChoices[2],
            $this->objectChoices[2]->name,
            $this->objectChoices[2]->name
        );
    }

    public function testArrayChoices()
    {
        $field = $this->getFactory()->createField('choice', 'choice', [
            'choices' => $this->choices,
        ]);

        $this->assertTransformedEquals($field, 'b', 'b', 'b');
    }

    public function testNumericChoices()
    {
        $field = $this->getFactory()->createField('choice', 'choice', [
            'choices' => $this->numericChoices,
        ]);

        $this->assertTransformedEquals($field, 2, 2, '2');
    }

    public function testNumericChoicesByLabel()
    {
        $field = $this->getFactory()->createField('choice', 'choice', [
            'label_as_value' => true,
            'choices' => $this->numericChoices,
        ]);

        $this->assertTransformedEquals($field, 2, $this->numericChoices[2], $this->numericChoices[2]);
    }

    // https://github.com/symfony/symfony/issues/10409

    public function testReuseNonUtf8ChoiceLists()
    {
        $field = $this->getFactory()->createField('choice', 'choice', [
            'choices' => [
                'meter' => 'm',
                'millimeter' => 'mm',
                'micrometer' => chr(181).'meter',
            ],
        ]);

        $field2 = $this->getFactory()->createField('choice', 'choice', [
            'choices' => [
                'meter' => 'm',
                'millimeter' => 'mm',
                'micrometer' => chr(181).'meter',
            ],
        ]);

        $field3 = $this->getFactory()->createField('choice', 'choice', [
            'choices' => [
                'meter' => 'm',
                'millimeter' => 'mm',
                'micrometer' => null,
            ],
        ]);

        // $field1 and $field2 use the same ChoiceList
        $this->assertSame(
            $field->getOption('choice_list'),
            $field2->getOption('choice_list')
        );

        // $field3 doesn't, but used to use the same when using json_encode()
        // instead of serialize for the hashing algorithm
        $this->assertNotSame(
            $field->getOption('choice_list'),
            $field3->getOption('choice_list')
        );
    }

    protected function getTestedType()
    {
        return 'choice';
    }
}
