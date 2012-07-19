<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Tests;

use Rollerworks\Bundle\RecordFilterBundle\Value\FilterValuesBag;
use Rollerworks\Bundle\RecordFilterBundle\Formatter\ModifierFormatter as Formatter;
use Rollerworks\Bundle\RecordFilterBundle\Type\Date;
use Rollerworks\Bundle\RecordFilterBundle\Type\DateTimeExtended;
use Rollerworks\Bundle\RecordFilterBundle\Type\Number;
use Rollerworks\Bundle\RecordFilterBundle\Input\FilterQuery as QueryInput;
use Rollerworks\Bundle\RecordFilterBundle\Value\Compare;
use Rollerworks\Bundle\RecordFilterBundle\Value\Range;
use Rollerworks\Bundle\RecordFilterBundle\Value\SingleValue;
use Rollerworks\Bundle\RecordFilterBundle\FilterField;

use Rollerworks\Bundle\RecordFilterBundle\Tests\Modifier\ModifierTestCase;
use Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\InvoiceType;

class FormatterTest extends ModifierTestCase
{
    public function testFormatterNoModifiers()
    {
        $input = new QueryInput($this->translator);
        $input->setField('user', FilterField::create('user', new Number(), true, true));
        $input->setField('status', FilterField::create('status', null, false, true));
        $input->setField('period', FilterField::create('period', new Date(), false, true));

        $input->setInput('User=2,3,10-"20"; Status=Active; period=29.10.2010');

        $formatter = $this->newFormatter(false);

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']   = new FilterValuesBag('user', '2,3,10-"20"', array(new SingleValue('2'), new SingleValue('3')), array(), array(2 => new Range('10', '20')), array(), array(), 2);
        $expectedValues['status'] = new FilterValuesBag('status', 'Active', array(new SingleValue('Active')), array(), array(), array(), array(), 0);
        $expectedValues['period'] = new FilterValuesBag('period', '29.10.2010', array(new SingleValue('29.10.2010')), array(), array(), array(), array(), 0);

        $this->assertEquals($expectedValues, $filters[0]);
    }

    // Output formatter

    public function testGetFilters()
    {
        $input = new QueryInput($this->translator);
        $input->setInput('User=2,3,10-20; Status=Active; date=29.10.2010; period=>20,10');

        $formatter = $this->newFormatter();
        $input->setField('user', FilterField::create('user', null, false, true));
        $input->setField('status', FilterField::create('status'));
        $input->setField('date', FilterField::create('date'));
        $input->setField('period', FilterField::create('period', null, false, false, true));

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']   = new FilterValuesBag('user', '2,3,10-20', array(new SingleValue('2'), new SingleValue('3')), array(), array(2 => new Range('10', '20')), array(), array(), 2);
        $expectedValues['status'] = new FilterValuesBag('status', 'Active', array(new SingleValue('Active')), array(), array(), array(), array(), 0);
        $expectedValues['date']   = new FilterValuesBag('date', '29.10.2010', array(new SingleValue('29.10.2010')), array(), array(), array(), array(), 0);
        $expectedValues['period'] = new FilterValuesBag('period', '>20,10', array(1 => new SingleValue('10')), array(), array(), array(0 => new Compare('20', '>')), array(), 1);

        $this->assertEquals($expectedValues, $filters[0]);
    }

    public function testGetFiltersNoPreviousErrors()
    {
        $input = new QueryInput($this->translator);
        $input->setField('user', FilterField::create('user', new Number(), false, true));
        $input->setField('status', FilterField::create('status'));
        $input->setField('date', FilterField::create('date'));
        $input->setField('period', FilterField::create('period', null, false, false, true));

        $input->setInput('User=2,5,20-10; Status=Active; date=29.10.2010; period=>20,10');
        $formatter = $this->newFormatter();

        $this->assertFalse($formatter->formatInput($input));

        $messages = $formatter->getMessages();

        $this->assertEquals(array("Validation error in field 'user': '20' is not lower then '10' in group 1."),  $messages['error']);

        $input->setInput('User=2,5,10-20; Status=Active; date=29.10.2010; period=>20,10');

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']   = new FilterValuesBag('user', '2,5,10-20', array(new SingleValue('2'), new SingleValue('5')), array(), array(2 => new Range('10', '20')), array(), array(), 2);
        $expectedValues['status'] = new FilterValuesBag('status', 'Active', array(new SingleValue('Active')), array(), array(), array(), array(), 0);
        $expectedValues['date']   = new FilterValuesBag('date', '29.10.2010', array(new SingleValue('29.10.2010')), array(), array(), array(), array(), 0);
        $expectedValues['period'] = new FilterValuesBag('period', '>20,10', array(1 => new SingleValue('10')), array(), array(), array(0 => new Compare('20', '>')), array(), 1);

        $this->assertCount(1, $filters);
        $this->assertEquals($expectedValues, $filters[0]);
    }

    public function testGetFiltersWithExcludes()
    {
        $input = new QueryInput($this->translator);
        $input->setInput('User=2,3,10-20,!15; Status=Active; date=29.10.2010; period=>20,10');

        $formatter = $this->newFormatter();
        $input->setField('user', FilterField::create('user', null, false, true));
        $input->setField('status', FilterField::create('status'));
        $input->setField('date', FilterField::create('date'));
        $input->setField('period', FilterField::create('period', null, false, false, true));

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']   = new FilterValuesBag('user', '2,3,10-20,!15', array(new SingleValue('2'), new SingleValue('3')), array(3 => new SingleValue('15')), array(2 => new Range('10', '20')), array(), array(), 3);
        $expectedValues['status'] = new FilterValuesBag('status', 'Active', array(new SingleValue('Active')), array(), array(), array(), array(), 0);
        $expectedValues['date']   = new FilterValuesBag('date', '29.10.2010', array(new SingleValue('29.10.2010')), array(), array(), array(), array(), 0);
        $expectedValues['period'] = new FilterValuesBag('period', '>20,10', array(1 => new SingleValue('10')), array(), array(), array(0 => new Compare('20', '>')), array(), 1);

        $this->assertCount(1, $filters);
        $this->assertEquals($expectedValues, $filters[0]);
    }

    public function testGetFiltersWithExcludedRanges()
    {
        $input = new QueryInput($this->translator);
        $input->setInput('User=2,3,20-50,!25-30; Status=Active; date=29.10.2010; period=>20,10');

        $formatter = $this->newFormatter();
        $input->setField('user', FilterField::create('user', null, false, true));
        $input->setField('status', FilterField::create('status'));
        $input->setField('date', FilterField::create('date'));
        $input->setField('period', FilterField::create('period', null, false, false, true));

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']   = new FilterValuesBag('user', '2,3,20-50,!25-30', array(new SingleValue('2'), new SingleValue('3')), array(), array(2 => new Range('20', '50')), array(), array(3 => new Range('25', '30')), 3);
        $expectedValues['status'] = new FilterValuesBag('status', 'Active', array(new SingleValue('Active')), array(), array(), array(), array(), 0);
        $expectedValues['date']   = new FilterValuesBag('date', '29.10.2010', array(new SingleValue('29.10.2010')), array(), array(), array(), array(), 0);
        $expectedValues['period'] = new FilterValuesBag('period', '>20,10', array(1 => new SingleValue('10')), array(), array(), array(0 => new Compare('20', '>')), array(), 1);

        $this->assertCount(1, $filters);
        $this->assertEquals($expectedValues, $filters[0]);
    }

    public function testGetFiltersEmptyFieldAndSingleValue()
    {
        $input = new QueryInput($this->translator);
        $input->setInput('User=2,,3,10-20; Status=Active; date=29.10.2010');

        $formatter = $this->newFormatter();
        $input->setField('user', FilterField::create('user', null, false, true));
        $input->setField('status', FilterField::create('status'));
        $input->setField('date', FilterField::create('date'));
        $input->setField('period', FilterField::create('period'));

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']   = new FilterValuesBag('user', '2,,3,10-20', array(new SingleValue('2'), new SingleValue('3')), array(), array(2 => new Range('10', '20')), array(), array(), 2);
        $expectedValues['status'] = new FilterValuesBag('status', 'Active', array(new SingleValue('Active')), array(), array(), array(), array(), 0);
        $expectedValues['date']   = new FilterValuesBag('date', '29.10.2010', array(new SingleValue('29.10.2010')), array(), array(), array(), array(), 0);

        $this->assertCount(1, $filters);
        $this->assertEquals($expectedValues, $filters[0]);
    }

    public function testQuoted()
    {
        $input = new QueryInput($this->translator);
        $input->setInput('User=2,3,10-20; Status=Active; date="29-10-2010"; period=>"20""","""20""",10');

        $formatter = $this->newFormatter();
        $input->setField('user', FilterField::create('user', null, false, true));
        $input->setField('status', FilterField::create('status'));
        $input->setField('date', FilterField::create('date'));
        $input->setField('period', FilterField::create('period', null, false, false, true));

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']   = new FilterValuesBag('user', '2,3,10-20', array(new SingleValue('2'), new SingleValue('3')), array(), array(2 => new Range('10', '20')), array(), array(), 2);
        $expectedValues['status'] = new FilterValuesBag('status', 'Active', array(new SingleValue('Active')), array(), array(), array(), array(), 0);
        $expectedValues['date']   = new FilterValuesBag('date', '"29-10-2010"', array(new SingleValue('29-10-2010')), array(), array(), array(), array(), 0);
        $expectedValues['period'] = new FilterValuesBag('period', '>"20""","""20""",10', array(1 => new SingleValue('"20"'), 2 => new SingleValue('10')), array(), array(), array(0 => new Compare('20"', '>')), array(), 2);

        $this->assertCount(1, $filters);
        $this->assertEquals($expectedValues, $filters[0]);
    }

    // Test Aliases

    public function testFieldAlias()
    {
        $input = new QueryInput($this->translator);
        $input->setInput('Gebruiker=2,3,10-20; Status=Active; datung=29.10.2010');

        $formatter = $this->newFormatter();
        $input->setLabelToField('user', 'gebruiker');
        $input->setLabelToField('date', array('datum', 'datung'));

        $input->setField('user', FilterField::create('gebruiker', null, true, true));
        $input->setField('status', FilterField::create('status', null, true, true));
        $input->setField('date', FilterField::create('datum', null, true, true));

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']   = new FilterValuesBag('gebruiker', '2,3,10-20', array(new SingleValue('2'), new SingleValue('3')), array(), array(2 => new Range('10', '20')), array(), array(), 2);
        $expectedValues['status'] = new FilterValuesBag('status', 'Active', array(new SingleValue('Active')), array(), array(), array(), array(), 0);
        $expectedValues['date']   = new FilterValuesBag('datum', '29.10.2010', array(new SingleValue('29.10.2010')), array(), array(), array(), array(), 0);

        $this->assertCount(1, $filters);
        $this->assertEquals($expectedValues, $filters[0]);
    }

    public function testFieldAliasByTranslator()
    {
        $input = new QueryInput($this->translator);
        $input->setInput('Gebruiker=2,3,10-20; Status=Active; datung=29.10.2010; periods=>20,10; cat=10');

        $this->translator->addResource('array', array('search' => array('gebruiker'    => 'user',
                                                                        'datum'        => 'date',
                                                                        'datung'       => 'date')), 'en', 'filter');

        $formatter = $this->newFormatter();
        $input->setLabelToFieldByTranslator('search.', 'filter');
        $input->setLabelToField('period', array('periods'));

        $input->setField('user', FilterField::create('gebruiker', null, true, true));
        $input->setField('status', FilterField::create('status', null, true, true));
        $input->setField('date', FilterField::create('datung', null, true, true));
        $input->setField('period', FilterField::create('periods', null, false, false, true));

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']   = new FilterValuesBag('gebruiker', '2,3,10-20', array(new SingleValue('2'), new SingleValue('3')), array(), array(2 => new Range('10', '20')), array(), array(), 2);
        $expectedValues['status'] = new FilterValuesBag('status', 'Active', array(new SingleValue('Active')), array(), array(), array(), array(), 0);
        $expectedValues['date']   = new FilterValuesBag('datung', '29.10.2010', array(new SingleValue('29.10.2010')), array(), array(), array(), array(), 0);
        $expectedValues['period'] = new FilterValuesBag('periods', '>20,10', array(1 => new SingleValue('10')), array(), array(), array(0 => new Compare('20', '>')), array(), 1);

        $this->assertCount(1, $filters);
        $this->assertEquals($expectedValues, $filters[0]);
    }

    public function testFieldAliasMerge()
    {
        $input = new QueryInput($this->translator);
        $input->setInput('User=2,3; Status=Active; datung=29.10.2010; datum=30.10.2010');

        $formatter = $this->newFormatter();
        $input->setLabelToField('date', array('datum', 'datung'));

        $input->setField('user', FilterField::create('user', null, true, true));
        $input->setField('status', FilterField::create('status', null, true, true));
        $input->setField('date', FilterField::create('datung', null, true, true));

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']   = new FilterValuesBag('user', '2,3', array(new SingleValue('2'), new SingleValue('3')), array(), array(), array(), array(), 1);
        $expectedValues['status'] = new FilterValuesBag('status', 'Active', array(new SingleValue('Active')), array(), array(), array(), array(), 0);
        $expectedValues['date']   = new FilterValuesBag('datung', '29.10.2010,30.10.2010', array(new SingleValue('29.10.2010'), new SingleValue('30.10.2010')), array(), array(), array(), array(), 1);

        $this->assertCount(1, $filters);
        $this->assertEquals($expectedValues, $filters[0]);
    }

    public function testFieldAliasMergeWithGroups()
    {
        $input = new QueryInput($this->translator);
        $input->setInput('(User=2,3; Status=Active; datung=29.10.2010; datum=30.10.2010;),(User=2,3; Status=Active; datung=29.10.2011; datum=30.10.2011;)');

        $formatter = $this->newFormatter();
        $input->setLabelToField('date', array('datum', 'datung'));

        $input->setField('user', FilterField::create('user', null, true, true));
        $input->setField('status', FilterField::create('status', null, true, true));
        $input->setField('date', FilterField::create('datung', null, true, true));

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues[0]['user']   = new FilterValuesBag('user', '2,3', array(new SingleValue('2'), new SingleValue('3')), array(), array(), array(), array(), 1);
        $expectedValues[0]['status'] = new FilterValuesBag('status', 'Active', array(new SingleValue('Active')), array(), array(), array(), array(), 0);
        $expectedValues[0]['date']   = new FilterValuesBag('datung', '29.10.2010,30.10.2010', array(new SingleValue('29.10.2010'), new SingleValue('30.10.2010')), array(), array(), array(), array(), 1);

        $expectedValues[1]['user']   = new FilterValuesBag('user', '2,3', array(new SingleValue('2'), new SingleValue('3')), array(), array(), array(), array(), 1);
        $expectedValues[1]['status'] = new FilterValuesBag('status', 'Active', array(new SingleValue('Active')), array(), array(), array(), array(), 0);
        $expectedValues[1]['date']   = new FilterValuesBag('datung', '29.10.2011,30.10.2011', array(new SingleValue('29.10.2011'), new SingleValue('30.10.2011')), array(), array(), array(), array(), 1);

        $this->assertEquals($expectedValues, $filters);
    }

    public function testFieldAliasMergeWithGroups2()
    {
        $input = new QueryInput($this->translator);
        $input->setInput('(User=2,3; Status=Active; datung=29.10.2010; datum=30.10.2010;),(User=2,3; Status=Active; datung=29.10.2011;)');

        $formatter = $this->newFormatter();
        $input->setLabelToField('date', array('datum', 'datung'));

        $input->setField('user', FilterField::create('user', null, true, true));
        $input->setField('status', FilterField::create('status', null, true, true));
        $input->setField('date', FilterField::create('datung', null, true, true));

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues[0]['user']   = new FilterValuesBag('user', '2,3', array(new SingleValue('2'), new SingleValue('3')), array(), array(), array(), array(), 1);
        $expectedValues[0]['status'] = new FilterValuesBag('status', 'Active', array(new SingleValue('Active')), array(), array(), array(), array(), 0);
        $expectedValues[0]['date']   = new FilterValuesBag('datung', '29.10.2010,30.10.2010', array(new SingleValue('29.10.2010'), new SingleValue('30.10.2010')), array(), array(), array(), array(), 1);

        $expectedValues[1]['user']   = new FilterValuesBag('user', '2,3', array(new SingleValue('2'), new SingleValue('3')), array(), array(), array(), array(), 1);
        $expectedValues[1]['status'] = new FilterValuesBag('status', 'Active', array(new SingleValue('Active')), array(), array(), array(), array(), 0);
        $expectedValues[1]['date']   = new FilterValuesBag('datung', '29.10.2011', array(new SingleValue('29.10.2011')), array(), array(), array(), array(), 0);

        $this->assertEquals($expectedValues, $filters);
    }

    // Value matcher

    public function testValueMatcher()
    {
        \Locale::setDefault('nl');

        $input = new QueryInput($this->translator);
        $input->setInput('User=2,3,10-20; Status=Active; date=29-10-2010; period=>20,10');

        $formatter = $this->newFormatter();
        $input->setField('user', FilterField::create('user', null, false, true));
        $input->setField('status', FilterField::create('status'));
        $input->setField('date', FilterField::create('date', new Date()));
        $input->setField('period', FilterField::create('period', null, false, false, true));

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']   = new FilterValuesBag('user', '2,3,10-20', array(new SingleValue('2'), new SingleValue('3')), array(), array(2 => new Range('10', '20')), array(), array(), 2);
        $expectedValues['status'] = new FilterValuesBag('status', 'Active', array(new SingleValue('Active')), array(), array(), array(), array(), 0);
        $expectedValues['date']   = new FilterValuesBag('date', '29-10-2010', array(new SingleValue(new DateTimeExtended('2010-10-29'), '29-10-2010')), array(), array(), array(), array(), 0);
        $expectedValues['period'] = new FilterValuesBag('period', '>20,10', array(1 => new SingleValue('10')), array(), array(), array(0 => new Compare('20', '>')), array(), 1);

        $this->assertEquals($expectedValues, $filters[0]);
    }

    public function testValueMatcher2()
    {
        $input = new QueryInput($this->translator);
        $input->setInput('User=2,3,10-20; invoice=F2010-48932,F2011-48932-F2012-48932; date=29-10/2010; period=>20,10');

        $formatter = $this->newFormatter();
        $input->setField('user', FilterField::create('user', null, false, true));
        $input->setField('invoice', FilterField::create('invoice', new InvoiceType(), false, true));
        $input->setField('date', FilterField::create('date', new Date()));
        $input->setField('period', FilterField::create('period', null, false, false, true));

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']    = new FilterValuesBag('user', '2,3,10-20', array(new SingleValue('2'), new SingleValue('3')), array(), array(2 => new Range('10', '20')), array(), array(), 2);
        $expectedValues['invoice'] = new FilterValuesBag('invoice', 'F2010-48932,F2011-48932-F2012-48932', array(new SingleValue('F2010-48932')), array(), array(1 => new Range('F2011-48932', 'F2012-48932')), array(), array(), 1);
        $expectedValues['date']    = new FilterValuesBag('date', '29-10/2010', array(new SingleValue(new DateTimeExtended('2010-10-29'), '29-10/2010')), array(), array(), array(), array(), 0);
        $expectedValues['period']  = new FilterValuesBag('period', '>20,10', array(1 => new SingleValue('10')), array(), array(), array(0 => new Compare('20', '>')), array(), 1);

        $this->assertEquals($expectedValues, $filters[0]);
    }

    public function testValueMatcherWithRange()
    {
        $input = new QueryInput($this->translator);
        $input->setInput('User=2,3,10-20; Status=Active; date=29-10-2010; period=>20,10');

        $formatter = $this->newFormatter();
        $input->setField('user', FilterField::create('user', null, false, true));
        $input->setField('status', FilterField::create('status'));
        $input->setField('date', FilterField::create('date', new Date()));
        $input->setField('period', FilterField::create('period', null, false, false, true));

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']   = new FilterValuesBag('user', '2,3,10-20', array(new SingleValue('2'), new SingleValue('3')), array(), array(2 => new Range('10', '20')), array(), array(), 2);
        $expectedValues['status'] = new FilterValuesBag('status', 'Active', array(new SingleValue('Active')), array(), array(), array(), array(), 0);
        $expectedValues['date']   = new FilterValuesBag('date', '29-10-2010', array(new SingleValue(new DateTimeExtended('2010-10-29'), '29-10-2010')), array(), array(), array(), array(), 0);
        $expectedValues['period'] = new FilterValuesBag('period', '>20,10', array(1 => new SingleValue('10')), array(), array(), array(0 => new Compare('20', '>')), array(), 1);

        $this->assertEquals($expectedValues, $filters[0]);
    }

    // Test failures

    public function testFieldAliasByTranslatorInValidPrefix()
    {
        $input = new QueryInput($this->translator);

        $this->setExpectedException('\InvalidArgumentException', 'Prefix must be an string and can not be empty');
        $input->setLabelToFieldByTranslator(false);
    }

    public function testFieldAliasByTranslatorInValidDomain()
    {
        $input = new QueryInput($this->translator);

        $this->setExpectedException('\InvalidArgumentException', 'Domain must be an string and can not be empty');
        $input->setLabelToFieldByTranslator('t.', false);
    }

    public function testGetFilterNoValidationPerformed()
    {
        $formatter = $this->newFormatter();

        $this->setExpectedException('\RuntimeException', 'formatInput() must be executed before calling this function.');
        $formatter->getFilters();
    }
}
