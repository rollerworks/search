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

use Rollerworks\RecordFilterBundle\Input\FilterQuery as QueryInput;
use Rollerworks\RecordFilterBundle\Value\FilterValuesBag;
use Rollerworks\RecordFilterBundle\Value\SingleValue;

class FilterQueryTest extends \PHPUnit_Framework_TestCase
{
    function testQuerySingleField()
    {
        $input = new QueryInput();
        $input->setField('user');

        $input->setInput('User=2');

        $this->assertEquals('User=2', $input->getQueryString());
        $this->assertEquals(array(array('user' => new FilterValuesBag('user', '2', array(new SingleValue('2')), array(), array(), array(), array(), 0))), $input->getGroups());
    }

    function testQuerySingleFieldWithSpaces()
    {
        $input = new QueryInput();
        $input->setField('user');

        $input->setInput('User = 2');

        $this->assertEquals(array(array('user' => new FilterValuesBag('user', '2', array(new SingleValue('2')), array(), array(), array(), array(), 0))), $input->getGroups());
    }

    function testQuerySingleFieldWithUnicode()
    {
        $input = new QueryInput();
        $input->setField('foo', 'ß');
        $input->setLabelToField('foo', 'ß');

        $input->setInput('ß = 2');

        $this->assertEquals(array(array('foo' => new FilterValuesBag('ß', '2', array(new SingleValue('2')), array(), array(), array(), array(), 0))), $input->getGroups());
    }

    function testQuerySingleFieldWithUnicodeNumber()
    {
        $input = new QueryInput();
        $input->setField('foo', 'ß۲');
        $input->setLabelToField('foo', 'ß۲');

        $input->setInput('ß۲ = 2');

        $this->assertEquals(array(array('foo' => new FilterValuesBag('ß۲', '2', array(new SingleValue('2')), array(), array(), array(), array(), 0))), $input->getGroups());
    }

    function testQueryMultipleFields()
    {
        $input = new QueryInput();
        $input->setField('user');
        $input->setField('status');

        $input->setInput('User=2; Status=Active');

        $this->assertEquals(array(array(
            'user' => new FilterValuesBag('user', '2', array(new SingleValue('2')), array(), array(), array(), array(), 0),
            'status' => new FilterValuesBag('status', 'Active', array(new SingleValue('Active')), array(), array(), array(), array(), 0)
        )), $input->getGroups());
    }

    function testQueryMultipleFieldsNoSpace()
    {
        $input = new QueryInput();
        $input->setField('user');
        $input->setField('status');

        $input->setInput('User=2;Status=Active');

        $this->assertEquals(array(array(
            'user' => new FilterValuesBag('user', '2', array(new SingleValue('2')), array(), array(), array(), array(), 0),
            'status' => new FilterValuesBag('status', 'Active', array(new SingleValue('Active')), array(), array(), array(), array(), 0)
        )), $input->getGroups());
    }

    // Field-name appears more then once
    function testQueryDoubleFields()
    {
        $input = new QueryInput();
        $input->setField('user');
        $input->setField('status');

        $input->setInput('User=2; Status=Active; User=3;');

        $this->assertEquals(array(array(
            'user' => new FilterValuesBag('user', '2,3', array(new SingleValue('2'), new SingleValue('3')), array(), array(), array(), array(), 1),
            'status' => new FilterValuesBag('status', 'Active', array(new SingleValue('Active')), array(), array(), array(), array(), 0),
        )), $input->getGroups());
    }

    // Test the escaping of the filter-delimiter
    function testEscapedFilter()
    {
        $input = new QueryInput();
        $input->setField('user');
        $input->setField('status');
        $input->setField('date');

        $input->setInput('User=2; Status="Active;None"; date="29-10-2010"');

        $this->assertEquals(array(array(
            'user' => new FilterValuesBag('user', '2', array(new SingleValue('2')), array(), array(), array(), array(), 0),
            'status' => new FilterValuesBag('status', '"Active;None"', array(new SingleValue('Active;None')), array(), array(), array(), array(), 0),
            'date' => new FilterValuesBag('date', '"29-10-2010"', array(new SingleValue('29-10-2010')), array(), array(), array(), array(), 0),
        )), $input->getGroups());
    }

    function testOrGroup()
    {
        $input = new QueryInput();
        $input->setField('user');
        $input->setField('status');
        $input->setField('date');

        $input->setInput('(User=2; Status="Active;None"; date="29-10-2010";),(User=3; Status=Concept; date="30-10-2010";)');

        $this->assertEquals(array(
            array(
                'user' => new FilterValuesBag('user', '2', array(new SingleValue('2')), array(), array(), array(), array(), 0),
                'status' => new FilterValuesBag('status', '"Active;None"', array(new SingleValue('Active;None')), array(), array(), array(), array(), 0),
                'date' => new FilterValuesBag('date', '"29-10-2010"', array(new SingleValue('29-10-2010')), array(), array(), array(), array(), 0),
            ),
            array(
                'user' => new FilterValuesBag('user', '3', array(new SingleValue('3')), array(), array(), array(), array(), 0),
                'status' => new FilterValuesBag('status', 'Concept', array(new SingleValue('Concept')), array(), array(), array(), array(), 0),
                'date' => new FilterValuesBag('date', '"30-10-2010"', array(new SingleValue('30-10-2010')), array(), array(), array(), array(), 0),
            ),
        ), $input->getGroups());
    }

    function testOrGroupValueWithBars()
    {
        $input = new QueryInput();
        $input->setField('user');
        $input->setField('status');
        $input->setField('date');

        $input->setInput('(User=2; Status="(Active;None)"; date="29-10-2010";),(User=3; Status=Concept; date="30-10-2010";)');

        $this->assertEquals(array(
            array(
                'user' => new FilterValuesBag('user', '2', array(new SingleValue('2')), array(), array(), array(), array(), 0),
                'status' => new FilterValuesBag('status', '"(Active;None)"', array(new SingleValue('(Active;None)')), array(), array(), array(), array(), 0),
                'date' => new FilterValuesBag('date', '"29-10-2010"', array(new SingleValue('29-10-2010')), array(), array(), array(), array(), 0),
            ),
            array(
                'user' => new FilterValuesBag('user', '3', array(new SingleValue('3')), array(), array(), array(), array(), 0),
                'status' => new FilterValuesBag('status', 'Concept', array(new SingleValue('Concept')), array(), array(), array(), array(), 0),
                'date' => new FilterValuesBag('date', '"30-10-2010"', array(new SingleValue('30-10-2010')), array(), array(), array(), array(), 0),
            ),
        ), $input->getGroups());
    }

    function testValidationNoRange()
    {
        $input = new QueryInput();
        $input->setField('User', null, null, true);
        $input->setField('status');
        $input->setField('date');

        $input->setInput('User=2-5; Status=Active; date=29.10.2010');

        $this->setExpectedException('Rollerworks\RecordFilterBundle\Exception\ValidationException', 'record_filter.no_range_support');
        $input->getGroups();
    }

    function testValidationNoCompare()
    {
        $input = new QueryInput();
        $input->setInput('User=2,3,10-20; Status=Active; date=25.05.2010,>25.5.2010');

        $input->setField('user', null, null, true, true);
        $input->setField('status', null, null, true, true);
        $input->setField('date', null, null, true, true);

        $this->setExpectedException('Rollerworks\RecordFilterBundle\Exception\ValidationException', 'record_filter.no_compare_support');
        $input->getGroups();
    }
}
