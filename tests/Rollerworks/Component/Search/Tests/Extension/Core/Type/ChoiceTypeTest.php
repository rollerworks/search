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

use Rollerworks\Component\Search\Extension\Core\ChoiceList\ObjectChoiceList;
use Rollerworks\Component\Search\Test\FieldTypeTestCase;

class ChoiceTypeTest extends FieldTypeTestCase
{
    private $choices = array(
        'a' => 'Bernhard',
        'b' => 'Fabien',
        'c' => 'Kris',
        'd' => 'Jon',
        'e' => 'Roman',
    );

    private $numericChoices = array(
        0 => 'Bernhard',
        1 => 'Fabien',
        2 => 'Kris',
        3 => 'Jon',
        4 => 'Roman',
    );

    private $objectChoices;

    protected function setUp()
    {
        parent::setUp();

        $this->objectChoices = array(
            (object) array('id' => 1, 'name' => 'Bernhard'),
            (object) array('id' => 2, 'name' => 'Fabien'),
            (object) array('id' => 3, 'name' => 'Kris'),
            (object) array('id' => 4, 'name' => 'Jon'),
            (object) array('id' => 5, 'name' => 'Roman'),
        );
    }

    protected function tearDown()
    {
        parent::tearDown();

        $this->objectChoices = null;
    }

    /**
     * @expectedException \PHPUnit_Framework_Error
     */
    public function testChoicesOptionExpectsArray()
    {
        $this->factory->createField('choice', 'choice', array(
            'choices' => new \ArrayObject(),
        ));
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     */
    public function testChoiceListOptionExpectsChoiceListInterface()
    {
        $this->factory->createField('choice', 'choice', array(
            'choice_list' => array('foo' => 'foo'),
        ));
    }

    public function testChoiceListAndChoicesCanBeEmpty()
    {
        $this->factory->createField('choice', 'choice');
    }

    public function testSubmitSingleNonExpandedInvalidChoice()
    {
        $this->factory->createField('choice', 'choice', array(
            'choices' => $this->choices,
        ));
    }

    public function testObjectChoices()
    {
        $field = $this->factory->createField('choice', 'choice', array(
            'choice_list' => new ObjectChoiceList(
                $this->objectChoices,
                // label path
                'name',
                // value path
                'id'
            ),
        ));

        $this->assertTransformedEquals(
            $field,
            $this->objectChoices[2],
            $this->objectChoices[2]->id,
            $this->objectChoices[2]->id
        );
    }

    public function testObjectChoicesByLabel()
    {
        $field = $this->factory->createField('choice', 'choice', array(
            'label_as_value' => true,
            'choice_list' => new ObjectChoiceList(
                $this->objectChoices,
                // label path
                'name',
                // value path
                'id'
            ),
        ));

        $this->assertTransformedEquals(
            $field,
            $this->objectChoices[2],
            $this->objectChoices[2]->name,
            $this->objectChoices[2]->name
        );
    }

    public function testArrayChoices()
    {
        $field = $this->factory->createField('choice', 'choice', array(
            'choices' => $this->choices,
        ));

        $this->assertTransformedEquals($field, 'b', 'b', 'b');
    }

    public function testNumericChoices()
    {
        $field = $this->factory->createField('choice', 'choice', array(
            'choices' => $this->numericChoices,
        ));

        $this->assertTransformedEquals($field, 2, 2, '2');
    }

    public function testNumericChoicesByLabel()
    {
        $field = $this->factory->createField('choice', 'choice', array(
            'label_as_value' => true,
            'choices' => $this->numericChoices,
        ));

        $this->assertTransformedEquals($field, 2, $this->numericChoices[2], $this->numericChoices[2]);
    }

    // https://github.com/symfony/symfony/issues/10409
    public function testReuseNonUtf8ChoiceLists()
    {
        $field = $this->factory->createField('choice', 'choice', array(
            'choices' => array(
                'meter' => 'm',
                'millimeter' => 'mm',
                'micrometer' => chr(181).'meter',
            ),
        ));

        $field2 = $this->factory->createField('choice', 'choice', array(
            'choices' => array(
                'meter' => 'm',
                'millimeter' => 'mm',
                'micrometer' => chr(181).'meter',
            ),
        ));

        $field3 = $this->factory->createField('choice', 'choice', array(
            'choices' => array(
                'meter' => 'm',
                'millimeter' => 'mm',
                'micrometer' => null,
            ),
        ));

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
