<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\TestAnnotation;

use Rollerworks\Bundle\RecordFilterBundle\Annotation\Field as FilterField;

class FieldTest extends \PHPUnit_Framework_TestCase
{
    public function testName()
    {
        $field = new FilterField(array('name' => 'User'));
        $this->assertEquals('User', $field->getName());

        $field = new FilterField(array('value' => 'User'));
        $this->assertEquals('User', $field->getName());
    }

    public function testNameReq()
    {
        $this->setExpectedException('\UnexpectedValueException', "Property 'name' on annotation 'Rollerworks\\Bundle\\RecordFilterBundle\\Annotation\\Field' is required.");
        new FilterField(array('type' => 'User'));
    }

    public function testRequired()
    {
        $field = new FilterField(array('name' => 'User', 'Required' => false));
        $this->assertFalse($field->isRequired());

        $field = new FilterField(array('name' => 'User', 'Required' => true));
        $this->assertTrue($field->isRequired());
    }

    public function testType()
    {
        $field = new FilterField(array('name' => 'User', 'Type' => 'Number'));
        $this->assertEquals('Number', $field->getType());
    }

    public function testAcceptRanges()
    {
        $field = new FilterField(array('name' => 'User', 'AcceptRanges' => true));
        $this->assertTrue($field->acceptsRanges());

        $field = new FilterField(array('name' => 'User', 'AcceptRanges' => false));
        $this->assertFalse($field->acceptsRanges());
    }

    public function testAcceptCompares()
    {
        $field = new FilterField(array('name' => 'User', 'AcceptCompares' => false));
        $this->assertFalse($field->acceptsCompares());

        $field = new FilterField(array('name' => 'User', 'AcceptCompares' => true));
        $this->assertTrue($field->acceptsCompares());
    }

    public function testConstructParams()
    {
        $field = new FilterField(array('name' => 'User', '_lang' => 'en' ));
        $this->assertEquals(array('lang' => 'en'), $field->getParams());
    }

    public function testUnknownProp()
    {
        $this->setExpectedException('\BadMethodCallException', "Unknown property 'doctor' on annotation 'Rollerworks\\Bundle\\RecordFilterBundle\\Annotation\\Field'.");
        new FilterField(array('name' => 'User', 'doctor' => 'who' ));
    }
}
