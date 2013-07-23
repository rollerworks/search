<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Tests\Formatter\Modifier;

use Rollerworks\Bundle\RecordFilterBundle\Type\Date;
use Rollerworks\Bundle\RecordFilterBundle\Type\DateTimeExtended;
use Rollerworks\Bundle\RecordFilterBundle\Type\Number;
use Rollerworks\Bundle\RecordFilterBundle\Input\FilterQuery as QueryInput;
use Rollerworks\Bundle\RecordFilterBundle\Value\FilterValuesBag;
use Rollerworks\Bundle\RecordFilterBundle\Value\SingleValue;
use Rollerworks\Bundle\RecordFilterBundle\Value\Compare;
use Rollerworks\Bundle\RecordFilterBundle\Value\Range;
use Rollerworks\Bundle\RecordFilterBundle\FilterField;

class DuplicateTest extends ModifierTestCase
{
    // Output formatter: Duplicates

    public function testDuplicates()
    {
        $input = new QueryInput($this->translator);
        $input->setInput('User=2,3,10-20; Status=Active; date=29.10.2010,29.10.2010; period=>20,10');

        $formatter = $this->newFormatter();
        $input->setField('user', FilterField::create('user', null, true, true));
        $input->setField('status', FilterField::create('status', null, true, true));
        $input->setField('date', FilterField::create('date', null, true, true));
        $input->setField('period', FilterField::create('period', null, true, true, true));

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $messages = $formatter->getMessages();
        $this->assertEquals(array('Duplicate value "29.10.2010" in field \'date\' in group 1 (removed).'), $messages['info']);

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']   = new FilterValuesBag('user', '2,3,10-20', array(new SingleValue('2'), new SingleValue('3')), array(), array(2 => new Range('10', '20')));
        $expectedValues['status'] = new FilterValuesBag('status', 'Active', array(new SingleValue('Active')));
        $expectedValues['date']   = new FilterValuesBag('date', '29.10.2010,29.10.2010', array(new SingleValue('29.10.2010')));
        $expectedValues['period'] = new FilterValuesBag('period', '>20,10', array(1 => new SingleValue('10')), array(), array(), array(0 => new Compare('20', '>')));

        $this->assertEquals($expectedValues, $filters[0]);
    }

    public function testDuplicatesMore()
    {
        $input = new QueryInput($this->translator);
        $input->setInput('User=2,3,10-20; Status=Active; date="29.10.2010",29.10.2010; period=>20,10');

        $formatter = $this->newFormatter();
        $input->setField('user', FilterField::create('user', null, true, true));
        $input->setField('status', FilterField::create('status', null, true, true));
        $input->setField('date', FilterField::create('date', null, true, true));
        $input->setField('period', FilterField::create('period', null, true, true, true));

        if (! $formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $messages = $formatter->getMessages();
        $this->assertEquals(array('Duplicate value "29.10.2010" in field \'date\' in group 1 (removed).'), $messages['info']);

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']   = new FilterValuesBag('user', '2,3,10-20', array(new SingleValue('2'), new SingleValue('3')), array(), array(2 => new Range('10', '20')));
        $expectedValues['status'] = new FilterValuesBag('status', 'Active', array(new SingleValue('Active')));
        $expectedValues['date']   = new FilterValuesBag('date', '"29.10.2010",29.10.2010', array(new SingleValue('29.10.2010')));
        $expectedValues['period'] = new FilterValuesBag('period', '>20,10', array(1 => new SingleValue('10')), array(), array(), array(0 => new Compare('20', '>')));

        $this->assertEquals($expectedValues, $filters[0]);
    }

    public function testDuplicatesWithType()
    {
        $input = new QueryInput($this->translator);
        $input->setInput('User=2,3,10-20; Status=Active; date="29.10.2010","29-10-2010",29.10.2010');

        $formatter = $this->newFormatter();
        $input->setField('user', FilterField::create('user', null, true, true));
        $input->setField('status', FilterField::create('status', null, true, true));
        $input->setField('date', FilterField::create('date', new Date(), true, true));

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $messages = $formatter->getMessages();
        $this->assertEquals(array('Duplicate value "29-10-2010" in field \'date\' in group 1 (removed).', 'Duplicate value "29.10.2010" in field \'date\' in group 1 (removed).'), $messages['info']);

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']   = new FilterValuesBag('user', '2,3,10-20', array(new SingleValue('2'), new SingleValue('3')), array(), array(2 => new Range('10', '20')));
        $expectedValues['status'] = new FilterValuesBag('status', 'Active', array(new SingleValue('Active')));
        $expectedValues['date']   = new FilterValuesBag('date', '"29.10.2010","29-10-2010",29.10.2010', array(new SingleValue(new DateTimeExtended('2010-10-29'), '29.10.2010')));

        $this->assertEquals($expectedValues, $filters[0]);
    }

    public function testDuplicatesWithTypeAndRange()
    {
        $input = new QueryInput($this->translator);
        $input->setInput('User=2,3,10-20; Status=Active; date=29.10.2010,29.10.2010,"29.10.2010"-"10.12.2010","29-10-2010"-10.12.2010,"29.10.2010"-"10.12.2010"');

        $formatter = $this->newFormatter();
        $input->setField('user', FilterField::create('user', null, true, true));
        $input->setField('status', FilterField::create('status', null, true, true));
        $input->setField('date', FilterField::create('date', new Date(), true, true));

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $messages = $formatter->getMessages();
        $this->assertEquals(array(
            'Duplicate value "29.10.2010" in field \'date\' in group 1 (removed).',
            'Duplicate value "29-10-2010"-"10.12.2010" in field \'date\' in group 1 (removed).',
            'Duplicate value "29.10.2010"-"10.12.2010" in field \'date\' in group 1 (removed).',
            'Value "29.10.2010" in field \'date\' is overlapping in range "29.10.2010"-"10.12.2010" in group 1 (removed).'
        ), $messages['info']);

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']   = new FilterValuesBag('user', '2,3,10-20', array(new SingleValue('2'), new SingleValue('3')), array(), array(2 => new Range('10', '20')));
        $expectedValues['status'] = new FilterValuesBag('status', 'Active', array(new SingleValue('Active')));
        $expectedValues['date']   = new FilterValuesBag('date', '29.10.2010,29.10.2010,"29.10.2010"-"10.12.2010","29-10-2010"-10.12.2010,"29.10.2010"-"10.12.2010"', array(), array(), array(2 => new Range(new DateTimeExtended('2010-10-29'), new DateTimeExtended('2010-12-10'), '29.10.2010', '10.12.2010')));

        $this->assertEquals($expectedValues, $filters[0]);
    }

    // For clarity an connected range is like 10-20,20-30 -> 10-30
    // The upper-value is equal to an other lower-value
    public function testDuplicatesWithTypeAndConnectedRange()
    {
        $input = new QueryInput($this->translator);
        $input->setInput('User=2,3,10-20; Status=Active; date=29.10.2010,29.10.2010, "29.10.2010"-"10.12.2010", "29-10-2010"-10.12.2010, "29.10.2010"-"10.12.2010","10-12-2010"-10.01.2011');

        $formatter = $this->newFormatter();
        $input->setField('user', FilterField::create('user', null, true, true));
        $input->setField('status', FilterField::create('status', null, true, true));
        $input->setField('date', FilterField::create('date', new Date(), true, true));

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $messages = $formatter->getMessages();
        $this->assertEquals(array(
            'Duplicate value "29.10.2010" in field \'date\' in group 1 (removed).',
            'Duplicate value "29-10-2010"-"10.12.2010" in field \'date\' in group 1 (removed).',
            'Duplicate value "29.10.2010"-"10.12.2010" in field \'date\' in group 1 (removed).',

            'Value "29.10.2010" in field \'date\' is overlapping in range "29.10.2010"-"10.12.2010" in group 1 (removed).',
            'Range upper-value of "29.10.2010"-"10.12.2010" equals lower-value of range "10-12-2010"-"10.01.2011" in field \'date\' in group 1 (ranges merged to "29.10.2010"-"10.01.2011").',
        ), $messages['info']);

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']   = new FilterValuesBag('user', '2,3,10-20', array(new SingleValue('2'), new SingleValue('3')), array(), array(2 => new Range('10', '20')));
        $expectedValues['status'] = new FilterValuesBag('status', 'Active', array(new SingleValue('Active')));
        $expectedValues['date']   = new FilterValuesBag('date', '29.10.2010,29.10.2010, "29.10.2010"-"10.12.2010", "29-10-2010"-10.12.2010, "29.10.2010"-"10.12.2010","10-12-2010"-10.01.2011', array(), array(), array(2 => new Range(new DateTimeExtended('2010-10-29'), new DateTimeExtended('2011-01-10'), '29.10.2010', '10.12.2010')));

        $this->assertEquals($expectedValues, $filters[0]);
    }

    // Make sure the connected ranges get merged, even when followed by duplicates
    public function testDuplicatesWithTypeAndConnectedRange2()
    {
        $input = new QueryInput($this->translator);
        $input->setInput('User=2,3,10-20; Status=Active; date=29.10.2010,29.10.2010, "29-10-2010"-10.12.2010, "29.10.2010"-"10.12.2010","10-12-2010"-10.01.2011, "29.10.2010"-"10.12.2010"');

        $formatter = $this->newFormatter();
        $input->setField('user', FilterField::create('user', null, true, true));
        $input->setField('status', FilterField::create('status', null, true, true));
        $input->setField('date', FilterField::create('date', new Date(), true, true));

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $messages = $formatter->getMessages();
        $this->assertEquals(array(
            'Duplicate value "29.10.2010" in field \'date\' in group 1 (removed).',
            'Duplicate value "29.10.2010"-"10.12.2010" in field \'date\' in group 1 (removed).',
            'Duplicate value "29.10.2010"-"10.12.2010" in field \'date\' in group 1 (removed).',

            'Value "29.10.2010" in field \'date\' is overlapping in range "29-10-2010"-"10.12.2010" in group 1 (removed).',
            'Range upper-value of "29-10-2010"-"10.12.2010" equals lower-value of range "10-12-2010"-"10.01.2011" in field \'date\' in group 1 (ranges merged to "29-10-2010"-"10.01.2011").',
        ), $messages['info']);

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']   = new FilterValuesBag('user', '2,3,10-20', array(new SingleValue('2'), new SingleValue('3')), array(), array(2 => new Range('10', '20')));
        $expectedValues['status'] = new FilterValuesBag('status', 'Active', array(new SingleValue('Active')));
        $expectedValues['date']   = new FilterValuesBag('date', '29.10.2010,29.10.2010, "29-10-2010"-10.12.2010, "29.10.2010"-"10.12.2010","10-12-2010"-10.01.2011, "29.10.2010"-"10.12.2010"', array(), array(), array(2 => new Range(new DateTimeExtended('2010-10-29'), new DateTimeExtended('2011-01-10'), '29-10-2010', '10.12.2010')));

        $this->assertEquals($expectedValues, $filters[0]);
    }

    // Make sure the connected ranges get merged, even when followed by overlapping ranges
    public function testDuplicatesWithTypeAndConnectedRange3()
    {
        $input = new QueryInput($this->translator);
        $input->setInput('User=2,3,10-20; Status=Active; date="10-12-2010"-10.01.2011, "29.10.2010"-"10.12.2010", "30.10.2010"-"08.12.2010"');//"30-10-2010"-01.01.2011

        $formatter = $this->newFormatter();
        $input->setField('user', FilterField::create('user', null, true, true));
        $input->setField('status', FilterField::create('status', null, true, true));
        $input->setField('date', FilterField::create('date', new Date(), true, true));

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $messages = $formatter->getMessages();
        $this->assertEquals(array(
            'Range upper-value of "29.10.2010"-"10.12.2010" equals lower-value of range "10-12-2010"-"10.01.2011" in field \'date\' in group 1 (ranges merged to "29.10.2010"-"10.01.2011").',
            'Range "30.10.2010"-"08.12.2010" in field \'date\' is overlapping in range "29.10.2010"-"10.12.2010" in group 1 (removed).'
        ), $messages['info']);

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']   = new FilterValuesBag('user', '2,3,10-20', array(new SingleValue('2'), new SingleValue('3')), array(), array(2 => new Range('10', '20')));
        $expectedValues['status'] = new FilterValuesBag('status', 'Active', array(new SingleValue('Active')));
        $expectedValues['date']   = new FilterValuesBag('date', '"10-12-2010"-10.01.2011, "29.10.2010"-"10.12.2010", "30.10.2010"-"08.12.2010"', array(), array(), array(1 => new Range(new DateTimeExtended('2010-10-29'), new DateTimeExtended('2011-01-10'), '29.10.2010', '10.12.2010')));

        $this->assertEquals($expectedValues, $filters[0]);
    }

    public function testDuplicatesWithTypeAndCompare()
    {
        $input = new QueryInput($this->translator);
        $input->setInput('User=2,3,10-20; Status=Active; date=25.05.2010,>25.5.2010,>"25.05.2010",<="25.05.2010","25-05-2010"');

        $formatter = $this->newFormatter();
        $input->setField('user', FilterField::create('user', null, true, true));
        $input->setField('status', FilterField::create('status', null, true, true));
        $input->setField('date', FilterField::create('date', new Date(), true, true, true));

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $messages = $formatter->getMessages();
        $this->assertEquals(array('Duplicate value "25-05-2010" in field \'date\' in group 1 (removed).', 'Duplicate value >"25.05.2010" in field \'date\' in group 1 (removed).'), $messages['info']);

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']   = new FilterValuesBag('user', '2,3,10-20', array(new SingleValue('2'), new SingleValue('3')), array(), array(2 => new Range('10', '20')));
        $expectedValues['status'] = new FilterValuesBag('status', 'Active', array(new SingleValue('Active')));
        $expectedValues['date']   = new FilterValuesBag('date', '25.05.2010,>25.5.2010,>"25.05.2010",<="25.05.2010","25-05-2010"', array(new SingleValue(new DateTimeExtended('2010-05-25'), '25.05.2010')), array(), array(), array(1 => new Compare(new DateTimeExtended('2010-05-25'), '>', '25.5.2010'),
                                                                                                                                                                                                    3 => new Compare(new DateTimeExtended('2010-05-25'), '<=', '25.05.2010')));

        $this->assertEquals($expectedValues, $filters[0]);
    }

    public function testDuplicatesWithTypeAndCompareGetValues()
    {
        $input = new QueryInput($this->translator);
        $input->setInput('User=2,3,10-20; Status=Active; date=25.05.2010,>25.5.2010,>"25.05.2010",<="25.05.2010","25-05-2010"');

        $formatter = $this->newFormatter();
        $input->setField('user', FilterField::create('user', null, true, true));
        $input->setField('status', FilterField::create('status', null, true, true));
        $input->setField('date', FilterField::create('date', new Date(), true, true, true));

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $messages = $formatter->getMessages();
        $this->assertEquals(array('Duplicate value "25-05-2010" in field \'date\' in group 1 (removed).', 'Duplicate value >"25.05.2010" in field \'date\' in group 1 (removed).'), $messages['info']);

        $groups = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues[0]['user']   = new FilterValuesBag('user', '2,3,10-20', array(new SingleValue('2'), new SingleValue('3')), array(), array(2 => new Range('10', '20')));
        $expectedValues[0]['status'] = new FilterValuesBag('status', 'Active', array(new SingleValue('Active')));
        $expectedValues[0]['date']   = new FilterValuesBag('date', '25.05.2010,>25.5.2010,>"25.05.2010",<="25.05.2010","25-05-2010"', array(new SingleValue(new DateTimeExtended('2010-05-25'), '25.05.2010')), array(), array(), array(1 => new Compare(new DateTimeExtended('2010-05-25'), '>', '25.5.2010'), 3 => new Compare(new DateTimeExtended('2010-05-25'), '<=', '25.05.2010')));

        $this->assertEquals($expectedValues, $groups);

        $input->setInput('
        (User=2,3,10-20; Status=Active; date=25.05.2010,>25.5.2010,>"25.05.2010",<="25.05.2010","25-05-2010";),
        (User=2,10-20; Status=Archived; date=26.05.2010,>26.5.2010,>"26.05.2010",<="26.05.2010","26-05-2010";)');

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $messages = $formatter->getMessages();
        $this->assertEquals(array(
            'Duplicate value "25-05-2010" in field \'date\' in group 1 (removed).',
            'Duplicate value >"25.05.2010" in field \'date\' in group 1 (removed).',
            'Duplicate value "26-05-2010" in field \'date\' in group 2 (removed).',
            'Duplicate value >"26.05.2010" in field \'date\' in group 2 (removed).',
        ), $messages['info']);

        $groups = $formatter->getFilters();

        $expectedValues = array();

        $expectedValues[0]['user']   = new FilterValuesBag('user', '2,3,10-20', array(new SingleValue('2'), new SingleValue('3')), array(), array(2 => new Range('10', '20')));
        $expectedValues[0]['status'] = new FilterValuesBag('status', 'Active', array(new SingleValue('Active')));
        $expectedValues[0]['date']   = new FilterValuesBag('date', '25.05.2010,>25.5.2010,>"25.05.2010",<="25.05.2010","25-05-2010"', array(new SingleValue(new DateTimeExtended('2010-05-25'), '25.05.2010')), array(), array(), array(1 => new Compare(new DateTimeExtended('2010-05-25'), '>', '25.5.2010'), 3 => new Compare(new DateTimeExtended('2010-05-25'), '<=', '25.05.2010')));

        $expectedValues[1]['user']   = new FilterValuesBag('user', '2,10-20', array(new SingleValue('2')), array(), array(1 => new Range('10', '20')));
        $expectedValues[1]['status'] = new FilterValuesBag('status', 'Archived', array(new SingleValue('Archived')));
        $expectedValues[1]['date']   = new FilterValuesBag('date', '26.05.2010,>26.5.2010,>"26.05.2010",<="26.05.2010","26-05-2010"', array(new SingleValue(new DateTimeExtended('2010-05-26'), '26.05.2010')), array(), array(), array(1 => new Compare(new DateTimeExtended('2010-05-26'), '>', '26.5.2010'), 3 => new Compare(new DateTimeExtended('2010-05-26'), '<=', '26.05.2010')));

        $this->assertEquals($expectedValues, $groups);
    }

    public function testRedundantCompare()
    {
        $input = new QueryInput($this->translator);
        $input->setInput('User=2,3,10-20; Status=Active; date=>25.05.2010,>=25.05.2010');

        $formatter = $this->newFormatter();
        $input->setField('user', FilterField::create('user', null, true, true));
        $input->setField('status', FilterField::create('status', null, true, true));
        $input->setField('date', FilterField::create('date', new Date(), true, true, true));

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $messages = $formatter->getMessages();
        $this->assertEquals(array('Comparison >"25.05.2010" is already covered by ">=" in field \'date\' in group 1 (removed).'), $messages['info']);

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']   = new FilterValuesBag('user', '2,3,10-20', array(new SingleValue('2'), new SingleValue('3')), array(), array(2 => new Range('10', '20')));
        $expectedValues['status'] = new FilterValuesBag('status', 'Active', array(new SingleValue('Active')));
        $expectedValues['date']   = new FilterValuesBag('date', '>25.05.2010,>=25.05.2010', array(), array(), array(), array(1 => new Compare(new DateTimeExtended('2010-05-25'), '>=', '25.05.2010')));

        $this->assertEquals($expectedValues, $filters[0]);
    }

    public function testDuplicatesWithTypeAndExclude()
    {
        $input = new QueryInput($this->translator);
        $input->setInput('User=2,4,10-20,!15,!"15"; Status=Active; date=25.05.2010');

        $formatter = $this->newFormatter();
        $input->setField('user', FilterField::create('user', new Number(), true, true));
        $input->setField('status', FilterField::create('status', null, true, true));
        $input->setField('date', FilterField::create('date', new Date(), true, true));

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $messages = $formatter->getMessages();
        $this->assertEquals(array('Duplicate value !"15" in field \'user\' in group 1 (removed).'), $messages['info']);

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']   = new FilterValuesBag('user', '2,4,10-20,!15,!"15"', array(new SingleValue('2'), new SingleValue('4')), array(3 => new SingleValue('15')), array(2 => new Range('10', '20')));
        $expectedValues['status'] = new FilterValuesBag('status', 'Active', array(new SingleValue('Active')));
        $expectedValues['date']   = new FilterValuesBag('date', '25.05.2010', array(new SingleValue(new DateTimeExtended('2010-05-25'), '25.05.2010')));

        $this->assertEquals($expectedValues, $filters[0]);
    }

    // Example: 5,1-10 will result in: 1-10
    public function testDuplicatesWithRange()
    {
        $input = new QueryInput($this->translator);
        $input->setInput('User=5,1-10; Status=Active; date=29.10.2010-29.12.2010,20.12.2010');

        $formatter = $this->newFormatter();
        $input->setField('user', FilterField::create('user', new Number(), true, true));
        $input->setField('status', FilterField::create('status', null, true, true));
        $input->setField('date', FilterField::create('date', new Date(), true, true));

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $messages = $formatter->getMessages();
        $this->assertEquals(array(
            'Value "5" in field \'user\' is overlapping in range "1"-"10" in group 1 (removed).',
            'Value "20.12.2010" in field \'date\' is overlapping in range "29.10.2010"-"29.12.2010" in group 1 (removed).'
        ), $messages['info']);

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']   = new FilterValuesBag('user', '5,1-10', array(), array(), array(1 => new Range('1', '10')));
        $expectedValues['status'] = new FilterValuesBag('status', 'Active', array(new SingleValue('Active')));
        $expectedValues['date']   = new FilterValuesBag('date', '29.10.2010-29.12.2010,20.12.2010', array(), array(), array(new Range(new DateTimeExtended('2010-10-29'), new DateTimeExtended('2010-12-29'), '29.10.2010', '29.12.2010')));

        $this->assertEquals($expectedValues, $filters[0]);
    }

    public function testDuplicatesWithExcludedRange()
    {
        $input = new QueryInput($this->translator);
        $input->setInput('User=2,4,!28,20-50,!25-30; Status=Active; date=29.10.2010; period=>20,10');

        $formatter = $this->newFormatter();
        $input->setField('user', FilterField::create('user', new Number(), false, true));
        $input->setField('status', FilterField::create('status'));
        $input->setField('date', FilterField::create('date'));
        $input->setField('period', FilterField::create('period', null, false, false, true));

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $messages = $formatter->getMessages();
        $this->assertEquals(array('Value !"28" in field \'user\' is overlapping in range !"25"-"30" in group 1 (removed).'), $messages['info']);

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']   = new FilterValuesBag('user', '2,4,!28,20-50,!25-30', array(new SingleValue('2'), new SingleValue('4')), array(), array(3 => new Range('20', '50')), array(), array(4 => new Range('25', '30')), 4);
        $expectedValues['status'] = new FilterValuesBag('status', 'Active', array(new SingleValue('Active')));
        $expectedValues['date']   = new FilterValuesBag('date', '29.10.2010', array(new SingleValue('29.10.2010')));
        $expectedValues['period'] = new FilterValuesBag('period', '>20,10', array(1 => new SingleValue('10')), array(), array(), array(0 => new Compare('20', '>')));

        $this->assertEquals($expectedValues, $filters[0]);
    }

    public function testDuplicatesWithExcludedRangeSameAsNormalRange()
    {
        $input = new QueryInput($this->translator);
        $input->setInput('User=2,3,25-30,!25-30; Status=Active; date=29.10.2010; period=>20,10');

        $formatter = $this->newFormatter();
        $input->setField('user', FilterField::create('user', new Number(), false, true));
        $input->setField('status', FilterField::create('status'));
        $input->setField('date', FilterField::create('date'));
        $input->setField('period', FilterField::create('period', null, false, false, true));

        $this->assertFalse($formatter->formatInput($input));

        $messages = $formatter->getMessages();
        $this->assertEquals(array('Excluded range "25"-"30" also exists as normal range in field \'user\' in group 1.'), $messages['error']);
    }

    // Example: 5,1-20,5-10 will result in: 1-20
    public function testDuplicatesWithRangeInRange()
    {
        $input = new QueryInput($this->translator);
        $input->setInput('User=5,1-20,5-10; Status=Active; date=29.10.2010-29.12.2010, 30.10.2010-20.12.2010');

        $formatter = $this->newFormatter();
        $input->setField('user', FilterField::create('user', new Number(), true, true));
        $input->setField('status', FilterField::create('status', null, true, true));
        $input->setField('date', FilterField::create('date', new Date(), true, true));

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $messages = $formatter->getMessages();
        $this->assertEquals(array(
            'Value "5" in field \'user\' is overlapping in range "1"-"20" in group 1 (removed).',
            'Range "5"-"10" in field \'user\' is overlapping in range "1"-"20" in group 1 (removed).',
            'Range "30.10.2010"-"20.12.2010" in field \'date\' is overlapping in range "29.10.2010"-"29.12.2010" in group 1 (removed).'
        ), $messages['info']);

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']   = new FilterValuesBag('user', '5,1-20,5-10', array(), array(), array(1 => new Range('1', '20')));
        $expectedValues['status'] = new FilterValuesBag('status', 'Active', array(new SingleValue('Active')));
        $expectedValues['date']   = new FilterValuesBag('date', '29.10.2010-29.12.2010, 30.10.2010-20.12.2010', array(), array(), array(new Range(new DateTimeExtended('2010-10-29'), new DateTimeExtended('2010-12-29'), '29.10.2010', '29.12.2010')));

        $this->assertEquals($expectedValues, $filters[0]);
    }
}
