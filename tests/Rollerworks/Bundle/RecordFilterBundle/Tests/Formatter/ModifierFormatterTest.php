<?php

/*
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Tests\Formatter;

use Rollerworks\Bundle\RecordFilterBundle\Value\FilterValuesBag;
use Rollerworks\Bundle\RecordFilterBundle\Type\Date;
use Rollerworks\Bundle\RecordFilterBundle\Type\Number;
use Rollerworks\Bundle\RecordFilterBundle\Input\FilterQuery as QueryInput;
use Rollerworks\Bundle\RecordFilterBundle\Value\Compare;
use Rollerworks\Bundle\RecordFilterBundle\Value\Range;
use Rollerworks\Bundle\RecordFilterBundle\Value\SingleValue;
use Rollerworks\Bundle\RecordFilterBundle\FilterField;

use Rollerworks\Bundle\RecordFilterBundle\Tests\Formatter\Modifier\ModifierTestCase;

class ModifierFormatterTest extends ModifierTestCase
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

    // Test failures

    public function testGetFilterNoValidationPerformed()
    {
        $formatter = $this->newFormatter();

        $this->setExpectedException('\RuntimeException', 'formatInput() must be executed before calling this function.');
        $formatter->getFilters();
    }
}
