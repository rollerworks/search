<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\RecordFilterBundle\Tests\Input;

use Rollerworks\RecordFilterBundle\Input\ArrayInput;


class ArrayTest extends \PHPUnit_Framework_TestCase
{
    function testSingleField()
    {
        $input = new ArrayInput(array('user' => '2'));
        $this->assertEquals(array(array('user' => '2')), $input->getValues());
    }

    function testSingleFieldWithUnicode()
    {
        $input = new ArrayInput(array('ß' => '2'));
        $this->assertEquals(array(array('ß' => '2')), $input->getValues());
    }

    function testMultipleFields()
    {
        $input = new ArrayInput(array('User' => '2', 'Status' => 'Active'));
        $this->assertEquals(array(array('user' => '2', 'status' => 'Active')), $input->getValues());
    }

    // Field-name appears more then once
    function testDoubleFields()
    {
        $input = new ArrayInput(array('User' => '2', 'Status' => 'Active', 'user' => '3'));
        $this->assertEquals(array(array('user' => '2,3', 'status' => 'Active')), $input->getValues());

        $input = new ArrayInput(array('User' => '2', 'Status' => 'Active', '0@User' => '3'));
        $this->assertEquals(array(array('user' => '2,3', 'status' => 'Active')), $input->getValues());
    }

    function testEscapedFilter()
    {
        $input = new ArrayInput(array('User' => '2', 'Status' => '"Active;None"', 'date' => '29-10-2010', ));

        $this->assertEquals(array(array('user' => '2','status' => '"Active;None"','date' => '29-10-2010')), $input->getValues());
    }

    function testOrGroup()
    {
        $input = new ArrayInput(array(
            array('User' => '2', 'Status' => '"Active;None"', 'date' => '29-10-2010'),
            array('User' => '3', 'Status' => 'Concept', 'date' => '30-10-2010')
        ));

        $this->assertEquals(array(
                array(
                    'user'   => '2',
                    'status' => '"Active;None"',
                    'date'   => '29-10-2010'
                ),

                array(
                    'user'   => '3',
                    'status' => 'Concept',
                    'date'   => '30-10-2010'
                ),
            ),

            $input->getValues());

        $this->assertTrue($input->hasGroups());

        $input = new ArrayInput(array(
            'User' => '2', 'Status' => '"Active;None"', 'date' => '29-10-2010',
            '1@User' => '3', '1@Status' => 'Concept', '1@date' => '30-10-2010'
        ));

        $this->assertEquals(array(
                array(
                    'user'   => '2',
                    'status' => '"Active;None"',
                    'date'   => '29-10-2010'
                ),

                array(
                    'user'   => '3',
                    'status' => 'Concept',
                    'date'   => '30-10-2010'
                ),
            ),

            $input->getValues());

        $this->assertTrue($input->hasGroups());

        $input = new ArrayInput(array(
            array('User' => '2', 'Status' => '"Active;None"', 'date' => '29-10-2010', 'user' => '3'),
            array('User' => '3', 'Status' => 'Concept', 'date' => '30-10-2010')
        ));

        $this->assertEquals(array(
                array(
                    'user'   => '2,3',
                    'status' => '"Active;None"',
                    'date'   => '29-10-2010'
                ),

                array(
                    'user'   => '3',
                    'status' => 'Concept',
                    'date'   => '30-10-2010'
                ),
            ),

            $input->getValues());

        $this->assertTrue($input->hasGroups());
    }

    function testOrGroupIllegalFieldName()
    {
        $input = new ArrayInput(array(
            array('User' => '2', 'Status' => '"Active;None"', 'date' => '29-10-2010', '0@User' => '3'),
            array('User' => '3', 'Status' => 'Concept', 'date' => '30-10-2010')
        ));

        $this->assertEquals(array(
                array(
                    'user'   => '2',
                    'status' => '"Active;None"',
                    'date'   => '29-10-2010'
                ),

                array(
                    'user'   => '3',
                    'status' => 'Concept',
                    'date'   => '30-10-2010'
                ),
            ),

            $input->getValues());

        $this->assertTrue($input->hasGroups());
    }

    function testIgnoreField()
    {
        $input = new ArrayInput(array('User' => '2', 'Status' => '"Active;None"', 'date' => '29-10-2010', 'period' => '20' ), array('period'));
        $this->assertEquals(array(array('user' => '2','status' => '"Active;None"','date' => '29-10-2010')), $input->getValues());

        $input = new ArrayInput(array('User' => '2', 'Status' => '"Active;None"', 'date' => '29-10-2010', 'Period' => '20' ), array('period'));
        $this->assertEquals(array(array('user' => '2','status' => '"Active;None"','date' => '29-10-2010')), $input->getValues());

        // Numeric key
        $input = new ArrayInput(array('User' => '2', 'Status' => '"Active;None"', 'date' => '29-10-2010', 'Period' => '20', 0 => '20' ), array('period', '0'));
        $this->assertEquals(array(array('user' => '2','status' => '"Active;None"', 'date' => '29-10-2010')), $input->getValues());
    }

    function testArrayAsGroupValue()
    {
        $this->setExpectedException('\UnexpectedValueException', 'Field value of "user" in group 0 must not be an array.');

        new ArrayInput(array(
            array('User' => array(2,3)),
        ));
    }

    function testValueArrayWrongKey()
    {
        $this->setExpectedException('\UnexpectedValueException', 'Value is an array but the key does not seem numeric, consider adding "user" to the ignore list.');

        new ArrayInput(array('User' => array(2,3)));
    }

    function testValueStringWrongKey()
    {
        $this->setExpectedException('\UnexpectedValueException', 'Value is not an array but the key seems numeric, consider adding "0" to the ignore list.');

        new ArrayInput(array(0 => '3'));
    }

    function testSetValue()
    {
        $input = new ArrayInput(array('User' => '2', 'Status' => 'Active'));
        $this->assertEquals(array(array('user' => '2', 'status' => 'Active')), $input->getValues());

        $input->setValue('User', '3');
        $this->assertEquals(array(array('user' => '3', 'status' => 'Active')), $input->getValues());

        $input->setValue('User', '3', 1);
        $this->assertEquals(array(array('user' => '3', 'status' => 'Active'), array('user' => '3')), $input->getValues());
    }

    function testSetValueNoLegalField()
    {
        $input = new ArrayInput(array('User' => '2', 'Status' => 'Active'));
        $this->assertEquals(array(array('user' => '2', 'status' => 'Active')), $input->getValues());

        $this->setExpectedException('\InvalidArgumentException', '$field is not an legal filter-field.');
        $input->setValue('@User', '3');
    }

    function testSetValueNoLegalValue()
    {
        $input = new ArrayInput(array('User' => '2', 'Status' => 'Active'));
        $this->assertEquals(array(array('user' => '2', 'status' => 'Active')), $input->getValues());

        $this->setExpectedException('\InvalidArgumentException', '$value must be an string value.');
        $input->setValue('User', false);
    }

    function testSetValueNoLegalGroup()
    {
        $input = new ArrayInput(array('User' => '2', 'Status' => 'Active'));
        $this->assertEquals(array(array('user' => '2', 'status' => 'Active')), $input->getValues());

        $this->setExpectedException('\InvalidArgumentException', '$group must be an positive integer or 0.');
        $input->setValue('User', '3', -1);
    }
}