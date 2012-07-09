<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Tests\Input;

use Rollerworks\Bundle\RecordFilterBundle\Input\FilterQuery as QueryInput;
use Rollerworks\Bundle\RecordFilterBundle\Value\FilterValuesBag;
use Rollerworks\Bundle\RecordFilterBundle\Value\SingleValue;
use Rollerworks\Bundle\RecordFilterBundle\Type\Date;
use Rollerworks\Bundle\RecordFilterBundle\Tests\TestCase;
use Rollerworks\Bundle\RecordFilterBundle\FilterField;

class FilterQueryTest extends TestCase
{
    public function testQuerySingleField()
    {
        $input = new QueryInput($this->translator);
        $input->setField('user', FilterField::create('user'));

        $input->setInput('User=2');

        $this->assertEquals('User=2', $input->getQueryString());
        $this->assertEquals(array(array('user' => new FilterValuesBag('user', '2', array(new SingleValue('2')), array(), array(), array(), array(), 0))), $input->getGroups());
    }

    public function testQuerySingleFieldWithSpaces()
    {
        $input = new QueryInput($this->translator);
        $input->setField('user', FilterField::create('user'));

        $input->setInput('User = 2');

        $this->assertEquals(array(array('user' => new FilterValuesBag('user', '2', array(new SingleValue('2')), array(), array(), array(), array(), 0))), $input->getGroups());
    }

    public function testQuerySingleFieldWithUnicode()
    {
        $input = new QueryInput($this->translator);
        $input->setField('foo', FilterField::create('ß'));
        $input->setLabelToField('foo', 'ß');

        $input->setInput('ß = 2');

        $this->assertEquals(array(array('foo' => new FilterValuesBag('ß', '2', array(new SingleValue('2')), array(), array(), array(), array(), 0))), $input->getGroups());
    }

    public function testQuerySingleFieldWithUnicodeNumber()
    {
        $input = new QueryInput($this->translator);
        $input->setField('foo', FilterField::create('ß۲'));
        $input->setLabelToField('foo', 'ß۲');

        $input->setInput('ß۲ = 2');

        $this->assertEquals(array(array('foo' => new FilterValuesBag('ß۲', '2', array(new SingleValue('2')), array(), array(), array(), array(), 0))), $input->getGroups());
    }

    public function testQueryMultipleFields()
    {
        $input = new QueryInput($this->translator);
        $input->setField('user', FilterField::create('user'));
        $input->setField('status', FilterField::create('status'));

        $input->setInput('User=2; Status=Active');

        $this->assertEquals(array(array(
            'user' => new FilterValuesBag('user', '2', array(new SingleValue('2')), array(), array(), array(), array(), 0),
            'status' => new FilterValuesBag('status', 'Active', array(new SingleValue('Active')), array(), array(), array(), array(), 0)
        )), $input->getGroups());
    }

    public function testQueryMultipleFieldsNoSpace()
    {
        $input = new QueryInput($this->translator);
        $input->setField('user', FilterField::create('user'));
        $input->setField('status', FilterField::create('status'));

        $input->setInput('User=2;Status=Active');

        $this->assertEquals(array(array(
            'user' => new FilterValuesBag('user', '2', array(new SingleValue('2')), array(), array(), array(), array(), 0),
            'status' => new FilterValuesBag('status', 'Active', array(new SingleValue('Active')), array(), array(), array(), array(), 0)
        )), $input->getGroups());
    }

    // Field-name appears more then once
    public function testQueryDoubleFields()
    {
        $input = new QueryInput($this->translator);
        $input->setField('user', FilterField::create('user'));
        $input->setField('status', FilterField::create('status'));

        $input->setInput('User=2; Status=Active; User=3;');

        $this->assertEquals(array(array(
            'user' => new FilterValuesBag('user', '2,3', array(new SingleValue('2'), new SingleValue('3')), array(), array(), array(), array(), 1),
            'status' => new FilterValuesBag('status', 'Active', array(new SingleValue('Active')), array(), array(), array(), array(), 0),
        )), $input->getGroups());
    }

    public function testQueryWithMatcher()
    {
        $input = new QueryInput($this->translator);
        $input->setField('date', FilterField::create('date', new Date()));

        $input->setInput('date=6-13-2012;');

        $this->assertEquals(array(array('date' => new FilterValuesBag('date', '6-13-2012', array(new SingleValue('6-13-2012', '6-13-2012'))))), $input->getGroups());
    }

    // Test the escaping of the filter-delimiter
    public function testEscapedFilter()
    {
        $input = new QueryInput($this->translator);
        $input->setField('user', FilterField::create('user'));
        $input->setField('status', FilterField::create('status'));
        $input->setField('date', FilterField::create('date'));

        $input->setInput('User=2; Status="Active;None"; date="29-10-2010"');

        $this->assertEquals(array(array(
            'user' => new FilterValuesBag('user', '2', array(new SingleValue('2')), array(), array(), array(), array(), 0),
            'status' => new FilterValuesBag('status', '"Active;None"', array(new SingleValue('Active;None')), array(), array(), array(), array(), 0),
            'date' => new FilterValuesBag('date', '"29-10-2010"', array(new SingleValue('29-10-2010')), array(), array(), array(), array(), 0),
        )), $input->getGroups());
    }

    public function testOrGroup()
    {
        $input = new QueryInput($this->translator);
        $input->setField('user', FilterField::create('user'));
        $input->setField('status', FilterField::create('status'));
        $input->setField('date', FilterField::create('date'));

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

    public function testOrGroupValueWithBars()
    {
        $input = new QueryInput($this->translator);
        $input->setField('user', FilterField::create('user'));
        $input->setField('status', FilterField::create('status'));
        $input->setField('date', FilterField::create('date'));

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

    public function testValidationNoRange()
    {
        $input = new QueryInput($this->translator);
        $input->setField('User', FilterField::create('User', null, true));
        $input->setField('status', FilterField::create('status'));
        $input->setField('date', FilterField::create('date'));

        $input->setInput('User=2-5; Status=Active; date=29.10.2010');

        $this->assertFalse($input->getGroups());
        $this->assertEquals(array("Field 'user' does not accept ranges in group 1."), $input->getMessages());
    }

    public function testValidationNoCompare()
    {
        $input = new QueryInput($this->translator);
        $input->setInput('User=2,3,10-20; Status=Active; date=25.05.2010,>25.5.2010');

        $input->setField('user', FilterField::create('user', null, true, true));
        $input->setField('status', FilterField::create('status', null, true, true));
        $input->setField('date', FilterField::create('date', null, true, true));

        $this->assertFalse($input->getGroups());
        $this->assertEquals(array("Field 'date' does not accept comparisons in group 1."), $input->getMessages());
    }
}
