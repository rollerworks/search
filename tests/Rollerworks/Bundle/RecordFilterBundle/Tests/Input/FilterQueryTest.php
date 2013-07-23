<?php

/*
 * This file is part of the RollerworksRecordFilterBundle package.
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
use Rollerworks\Bundle\RecordFilterBundle\Value\Range;
use Rollerworks\Bundle\RecordFilterBundle\Value\Compare;
use Rollerworks\Bundle\RecordFilterBundle\Type\Date;
use Rollerworks\Bundle\RecordFilterBundle\Tests\TestCase;
use Rollerworks\Bundle\RecordFilterBundle\FilterField;

class FilterQueryTest extends TestCase
{
    public function testSingleField()
    {
        $input = new QueryInput($this->translator);
        $input->setField('user', FilterField::create('user'));

        $input->setInput('User=2');

        $this->assertEquals('User=2', $input->getQueryString());
        $this->assertEquals(array(array('user' => new FilterValuesBag('user', '2', array(new SingleValue('2'))))), $input->getGroups());
    }

    public function testSingleFieldWithSpaces()
    {
        $input = new QueryInput($this->translator);
        $input->setField('user', FilterField::create('user'));

        $input->setInput('User = 2');

        $this->assertEquals(array(array('user' => new FilterValuesBag('user', '2', array(new SingleValue('2'))))), $input->getGroups());
    }

    public function testSingleFieldWithUnicode()
    {
        $input = new QueryInput($this->translator);
        $input->setField('foo', FilterField::create('ß'));
        $input->setLabelToField('foo', 'ß');

        $input->setInput('ß = 2');

        $this->assertEquals(array(array('foo' => new FilterValuesBag('ß', '2', array(new SingleValue('2'))))), $input->getGroups());
    }

    public function testSingleFieldWithUnicodeNumber()
    {
        $input = new QueryInput($this->translator);
        $input->setField('foo', FilterField::create('ß۲'));
        $input->setLabelToField('foo', 'ß۲');

        $input->setInput('ß۲ = 2');

        $this->assertEquals(array(array('foo' => new FilterValuesBag('ß۲', '2', array(new SingleValue('2'))))), $input->getGroups());
    }

    public function testMultipleFields()
    {
        $input = new QueryInput($this->translator);
        $input->setField('user', FilterField::create('user'));
        $input->setField('status', FilterField::create('status'));

        $input->setInput('User=2; Status=Active');

        $this->assertEquals(array(array(
            'user' => new FilterValuesBag('user', '2', array(new SingleValue('2'))),
            'status' => new FilterValuesBag('status', 'Active', array(new SingleValue('Active')))
        )), $input->getGroups());
    }

    public function testMultipleFieldsNoSpace()
    {
        $input = new QueryInput($this->translator);
        $input->setField('user', FilterField::create('user'));
        $input->setField('status', FilterField::create('status'));

        $input->setInput('User=2;Status=Active');

        $this->assertEquals(array(array(
            'user' => new FilterValuesBag('user', '2', array(new SingleValue('2'))),
            'status' => new FilterValuesBag('status', 'Active', array(new SingleValue('Active')))
        )), $input->getGroups());
    }

    public function testEmptyValue()
    {
        $input = new QueryInput($this->translator);
        $input->setField('user', FilterField::create('user'));

        $input->setInput('User=2,,3');

        $this->assertEquals('User=2,,3', $input->getQueryString());
        $this->assertEquals(array(array('user' => new FilterValuesBag('user', '2,,3', array(new SingleValue('2'), new SingleValue('3'))))), $input->getGroups());
    }

    // Field-name appears more then once
    public function testDuplicateFields()
    {
        $input = new QueryInput($this->translator);
        $input->setField('user', FilterField::create('user'));
        $input->setField('status', FilterField::create('status'));

        $input->setInput('User=2; Status=Active; User=3;');

        $this->assertEquals(array(array(
            'user' => new FilterValuesBag('user', '2,3', array(new SingleValue('2'), new SingleValue('3'))),
            'status' => new FilterValuesBag('status', 'Active', array(new SingleValue('Active'))),
        )), $input->getGroups());
    }

    public function testDuplicateFieldsAlias()
    {
        $input = new QueryInput($this->translator);
        $input->setField('user', FilterField::create('user'));
        $input->setField('status', FilterField::create('status'));
        $input->setLabelToField('user', 'gebruiker');

        $input->setInput('User=2; Status=Active; gebruiker=3;');

        $this->assertEquals(array(array(
            'user' => new FilterValuesBag('user', '2,3', array(new SingleValue('2'), new SingleValue('3'))),
            'status' => new FilterValuesBag('status', 'Active', array(new SingleValue('Active'))),
        )), $input->getGroups());
    }

    public function testWithMatcher()
    {
        $input = new QueryInput($this->translator);
        $input->setField('date', FilterField::create('date', new Date()));

        $input->setInput('date=6-13-2012;');

        $this->assertEquals(array(array('date' => new FilterValuesBag('date', '6-13-2012', array(new SingleValue('6-13-2012', '6-13-2012'))))), $input->getGroups());
    }

    public function testWithMatcherWithRange()
    {
        $input = new QueryInput($this->translator);
        $input->setField('date', FilterField::create('date', new Date(), false, true));

        $input->setInput('date=3-12-2012,6-12-2012-8-12-2012;');

        $this->assertEquals(array(array('date' => new FilterValuesBag('date', '3-12-2012,6-12-2012-8-12-2012', array(new SingleValue('3-12-2012')), array(), array(1 => new Range('6-12-2012', '8-12-2012'))))), $input->getGroups());
    }

    public function testQuoted()
    {
        $input = new QueryInput($this->translator);
        $input->setField('user', FilterField::create('user'));
        $input->setField('status', FilterField::create('status'));
        $input->setField('date', FilterField::create('date'));

        $input->setInput('User=2; Status="Active;None"; date="29-10-2010"');

        $this->assertEquals(array(array(
            'user' => new FilterValuesBag('user', '2', array(new SingleValue('2'))),
            'status' => new FilterValuesBag('status', '"Active;None"', array(new SingleValue('Active;None'))),
            'date' => new FilterValuesBag('date', '"29-10-2010"', array(new SingleValue('29-10-2010'))),
        )), $input->getGroups());
    }

    public function testQuotedComplex()
    {
        $input = new QueryInput($this->translator);
        $input->setField('user', FilterField::create('user', null, false, true));
        $input->setField('status', FilterField::create('status'));
        $input->setField('date', FilterField::create('date'));
        $input->setField('period', FilterField::create('period', null, false, false, true));

        $input->setInput('User=2,3,"10-20",!"15",10-20; Status=Active; date="29-10-2010"; period=>"20""","""20""",10');

        $this->assertEquals(array(array(
            'user' => new FilterValuesBag('user', '2,3,"10-20",!"15",10-20', array(new SingleValue('2'), new SingleValue('3'), new SingleValue('10-20')), array(3 => new SingleValue('15')), array(4 => new Range('10', '20'))),
            'status' => new FilterValuesBag('status', 'Active', array(new SingleValue('Active'))),
            'date' => new FilterValuesBag('date', '"29-10-2010"', array(new SingleValue('29-10-2010'))),
            'period' => new FilterValuesBag('period', '>"20""","""20""",10', array(1 => new SingleValue('"20"'), 2 => new SingleValue('10')), array(), array(), array(0 => new Compare('20"', '>'))),
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
                'user' => new FilterValuesBag('user', '2', array(new SingleValue('2'))),
                'status' => new FilterValuesBag('status', '"Active;None"', array(new SingleValue('Active;None'))),
                'date' => new FilterValuesBag('date', '"29-10-2010"', array(new SingleValue('29-10-2010'))),
            ),
            array(
                'user' => new FilterValuesBag('user', '3', array(new SingleValue('3'))),
                'status' => new FilterValuesBag('status', 'Concept', array(new SingleValue('Concept'))),
                'date' => new FilterValuesBag('date', '"30-10-2010"', array(new SingleValue('30-10-2010'))),
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
                'user' => new FilterValuesBag('user', '2', array(new SingleValue('2'))),
                'status' => new FilterValuesBag('status', '"(Active;None)"', array(new SingleValue('(Active;None)'))),
                'date' => new FilterValuesBag('date', '"29-10-2010"', array(new SingleValue('29-10-2010'))),
            ),
            array(
                'user' => new FilterValuesBag('user', '3', array(new SingleValue('3'))),
                'status' => new FilterValuesBag('status', 'Concept', array(new SingleValue('Concept'))),
                'date' => new FilterValuesBag('date', '"30-10-2010"', array(new SingleValue('30-10-2010'))),
            ),
        ), $input->getGroups());
    }

    // Field-name appears more then once
    public function testOrGroupDuplicateFields()
    {
        $input = new QueryInput($this->translator);
        $input->setField('user', FilterField::create('user'));
        $input->setField('status', FilterField::create('status'));

        $input->setInput('(User=2; Status=Active; User=3;),(User=3; Status=Concept; date="30-10-2010";)');

        $this->assertEquals(array(
            array(
                'user' => new FilterValuesBag('user', '2,3', array(new SingleValue('2'), new SingleValue('3'))),
                'status' => new FilterValuesBag('status', 'Active', array(new SingleValue('Active'))),
            ),
            array(
                'user' => new FilterValuesBag('user', '3', array(new SingleValue('3'))),
                'status' => new FilterValuesBag('status', 'Concept', array(new SingleValue('Concept'))),
            ),
        ), $input->getGroups());
    }

    public function testOrGroupDuplicateFieldsAlias()
    {
        $input = new QueryInput($this->translator);
        $input->setField('user', FilterField::create('user'));
        $input->setField('status', FilterField::create('status'));
        $input->setLabelToField('user', 'gebruiker');

        $input->setInput('(User=2; Status=Active; gebruiker=3;),(User=3; Status=Concept; date="30-10-2010";)');

        $this->assertEquals(array(
            array(
                'user' => new FilterValuesBag('user', '2,3', array(new SingleValue('2'), new SingleValue('3'))),
                'status' => new FilterValuesBag('status', 'Active', array(new SingleValue('Active'))),
            ),
            array(
                'user' => new FilterValuesBag('user', '3', array(new SingleValue('3'))),
                'status' => new FilterValuesBag('status', 'Concept', array(new SingleValue('Concept'))),
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

    public function testRequired()
    {
        $input = new QueryInput($this->translator);
        $input->setField('user', FilterField::create('user', null, true));
        $input->setField('status', FilterField::create('status'));

        $input->setInput('Status=Active');

        $this->assertFalse($input->getGroups());
        $this->assertEquals(array("Field 'user' is required in group 1."), $input->getMessages());
    }

    public function testRequired2()
    {
        $input = new QueryInput($this->translator);
        $input->setField('user', FilterField::create('user', null, true));
        $input->setField('status', FilterField::create('status'));

        $input->setInput('(User=2; Status=Active;), (Status=Active;)');

        $this->assertFalse($input->getGroups());
        $this->assertEquals(array("Field 'user' is required in group 2."), $input->getMessages());
    }

    public function testLimitGroups()
    {
        $input = new QueryInput($this->translator);
        $input->setLimitGroups(2);

        $input->setInput('
            (User=2,3; Status=Active; date=25.05.2010,30.5.2010,10.5.2010;),
            (User=2,3; Status=Active; date=25.05.2010,30.5.2010,10.5.2010;),
            (User=2,3; Status=Active; date=25.05.2010,30.5.2010,10.5.2010;)'
        );

        $input->setField('user', FilterField::create('user', null, false, true, true));
        $input->setField('status', FilterField::create('status', null, false, true, true));
        $input->setField('date', FilterField::create('date', null, true, true, true));

        $this->assertFalse($input->getGroups());
        $this->assertEquals(array("Only 2 groups or less are accepted."), $input->getMessages());
    }

    public function testLimitValues()
    {
        $input = new QueryInput($this->translator);
        $input->setLimitValues(2);

        $input->setInput('User=2,3; Status=Active; date=25.05.2010,30.5.2010,10.5.2010');

        $input->setField('user', FilterField::create('user', null, false, true, true));
        $input->setField('status', FilterField::create('status', null, false, true, true));
        $input->setField('date', FilterField::create('date', null, true, true, true));

        $this->assertFalse($input->getGroups());
        $this->assertEquals(array("Field 'date' in group 1 may only contain 2 values or less."), $input->getMessages());
    }

    public function testFieldAliasByTranslatorInvalidDomain()
    {
        $input = new QueryInput($this->translator);

        $this->setExpectedException('\InvalidArgumentException', 'Domain must be a string and can not be empty.');
        $input->setLabelToFieldByTranslator('t.', false);
    }
}
