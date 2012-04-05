<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\RecordFilterBundle\Tests\Modifier;

use Rollerworks\RecordFilterBundle\FilterValuesBag;
use Rollerworks\RecordFilterBundle\Formatter\Formatter;
use Rollerworks\RecordFilterBundle\Formatter\ModifiersRegistry;

use Rollerworks\RecordFilterBundle\Formatter\Modifier\Validator;
use Rollerworks\RecordFilterBundle\Formatter\Modifier\DuplicateRemove;
use Rollerworks\RecordFilterBundle\Formatter\Modifier\ValuesToRange;
use Rollerworks\RecordFilterBundle\Formatter\Modifier\RangeNormalizer;
use Rollerworks\RecordFilterBundle\Formatter\Modifier\CompareNormalizer;
use Rollerworks\RecordFilterBundle\Formatter\Modifier\ValueOptimizer;
use Rollerworks\RecordFilterBundle\Type\Date;
use Rollerworks\RecordFilterBundle\Type\DateTime;
use Rollerworks\RecordFilterBundle\Type\Decimal;
use Rollerworks\RecordFilterBundle\Type\Number;
use Rollerworks\RecordFilterBundle\Input\Query as QueryInput;
use Rollerworks\RecordFilterBundle\Value\Compare;
use Rollerworks\RecordFilterBundle\Value\Range;
use Rollerworks\RecordFilterBundle\Value\SingleValue;

use Rollerworks\RecordFilterBundle\Tests\Fixtures\InvoiceType;

class FormatterTest extends TestCase
{
    function testFormatterNoModifiers()
    {
        $input = new QueryInput();
        $input->setQueryString('User=2,3,10-"20"; Status=Active; period=29.10.2010');

        $formatter = $this->newFormatter(false);
        $formatter->setField('user', new Number(), true, true);
        $formatter->setField('status', null, false, true);
        $formatter->setField('period', new Date(), false, true);

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

    function testGetFilters()
    {
        $input = new QueryInput();
        $input->setQueryString('User=2,3,10-20; Status=Active; date=29.10.2010; period=>20,10');

        $formatter = $this->newFormatter();
        $formatter->setField('user', null, false, true);
        $formatter->setField('status');
        $formatter->setField('date');
        $formatter->setField('period', null, false, false, true);

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

    function testGetValues()
    {
        $input = new QueryInput();
        $input->setQueryString('User=2,3,10-20; Status=Active; date=29.10.2010; period=>20,10');

        $formatter = $this->newFormatter();
        $formatter->setField('user', null, false, true);
        $formatter->setField('status');
        $formatter->setField('date', new Date());
        $formatter->setField('period', null, false, false, true);

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $aValues = $formatter->getFiltersValues();

        $expectedValues = array();
        $expectedValues[0]['user']   = array('2', '3', '10-20');
        $expectedValues[0]['status'] = array('Active');
        $expectedValues[0]['date']   = array('29.10.2010');
        $expectedValues[0]['period'] = array('>20', '10');

        $this->assertEquals($expectedValues, $aValues);

        $this->assertEquals('user=2, 3, 10-20; status=Active; date=29.10.2010; period=>20, 10;', $formatter->getFiltersValues(true));
        $this->assertEquals('user=2, 3, 10-20;'.PHP_EOL.'status=Active;'.PHP_EOL.'date=29.10.2010;'.PHP_EOL.'period=>20, 10;', $formatter->getFiltersValues(true, true));
    }

    function testGetFiltersNoPreviousErrors()
    {
        $input = new QueryInput();
        $input->setQueryString('User=2,5,20-10; Status=Active; date=29.10.2010; period=>20,10');

        $formatter = $this->newFormatter();
        $formatter->setField('user', new Number(), false, true);
        $formatter->setField('status');
        $formatter->setField('date');
        $formatter->setField('period', null, false, false, true);
        $this->assertFalse($formatter->formatInput($input));

        $messages = $formatter->getMessages();

        $this->assertEquals(array("Validation error in field 'user': '20' is not lower then '10'"),  $messages['error']);

        $input = new QueryInput();
        $input->setQueryString('User=2,5,10-20; Status=Active; date=29.10.2010; period=>20,10');

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']   = new FilterValuesBag('user', '2,5,10-20', array(new SingleValue('2'), new SingleValue('5')), array(), array(2 => new Range('10', '20')), array(), array(), 2);
        $expectedValues['status'] = new FilterValuesBag('status', 'Active', array(new SingleValue('Active')), array(), array(), array(), array(), 0);
        $expectedValues['date']   = new FilterValuesBag('date', '29.10.2010', array(new SingleValue('29.10.2010')), array(), array(), array(), array(), 0);
        $expectedValues['period'] = new FilterValuesBag('period', '>20,10', array(1 => new SingleValue('10')), array(), array(), array(0 => new Compare('20', '>')), array(), 1);

        $this->assertEquals($expectedValues, $filters[0]);
    }

    function testGetFiltersWithExcludes()
    {
        $input = new QueryInput();
        $input->setQueryString('User=2,3,10-20,!15; Status=Active; date=29.10.2010; period=>20,10');

        $formatter = $this->newFormatter();
        $formatter->setField('user', null, false, true);
        $formatter->setField('status');
        $formatter->setField('date');
        $formatter->setField('period', null, false, false, true);

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user'] = new FilterValuesBag('user', '2,3,10-20,!15', array(new SingleValue('2'), new SingleValue('3')), array(3 => new SingleValue('15')), array(2 => new Range('10', '20')), array(), array(), 3);
        $expectedValues['status'] = new FilterValuesBag('status', 'Active', array(new SingleValue('Active')), array(), array(), array(), array(), 0);
        $expectedValues['date'] = new FilterValuesBag('date', '29.10.2010', array(new SingleValue('29.10.2010')), array(), array(), array(), array(), 0);
        $expectedValues['period'] = new FilterValuesBag('period', '>20,10', array(1 => new SingleValue('10')), array(), array(), array(0 => new Compare('20', '>')), array(), 1);

        $this->assertEquals($expectedValues, $filters[0]);
    }

    function testGetFiltersWithExcludedRanges()
    {
        $input = new QueryInput();
        $input->setQueryString('User=2,3,20-50,!25-30; Status=Active; date=29.10.2010; period=>20,10');

        $formatter = $this->newFormatter();
        $formatter->setField('user', null, false, true);
        $formatter->setField('status');
        $formatter->setField('date');
        $formatter->setField('period', null, false, false, true);

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']   = new FilterValuesBag('user', '2,3,20-50,!25-30', array(new SingleValue('2'), new SingleValue('3')), array(), array(2 => new Range('20', '50')), array(), array(3 => new Range('25', '30')), 3);
        $expectedValues['status'] = new FilterValuesBag('status', 'Active', array(new SingleValue('Active')), array(), array(), array(), array(), 0);
        $expectedValues['date']   = new FilterValuesBag('date', '29.10.2010', array(new SingleValue('29.10.2010')), array(), array(), array(), array(), 0);
        $expectedValues['period'] = new FilterValuesBag('period', '>20,10', array(1 => new SingleValue('10')), array(), array(), array(0 => new Compare('20', '>')), array(), 1);

        $this->assertEquals($expectedValues, $filters[0]);
    }

    function testGetFiltersEmptyFieldAndSingleValue()
    {
        $input = new QueryInput();
        $input->setQueryString('User=2,,3,10-20; Status=Active; date=29.10.2010');

        $formatter = $this->newFormatter();
        $formatter->setField('user', null, false, true);
        $formatter->setField('status');
        $formatter->setField('date');
        $formatter->setField('period');

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']   = new FilterValuesBag('user', '2,,3,10-20', array(new SingleValue('2'), new SingleValue('3')), array(), array(2 => new Range('10', '20')), array(), array(), 2);
        $expectedValues['status'] = new FilterValuesBag('status', 'Active', array(new SingleValue('Active')), array(), array(), array(), array(), 0);
        $expectedValues['date']   = new FilterValuesBag('date', '29.10.2010', array(new SingleValue('29.10.2010')), array(), array(), array(), array(), 0);

        $this->assertEquals($expectedValues, $filters[0]);
    }

    function testQuoted()
    {
        $input = new QueryInput();
        $input->setQueryString('User=2,3,10-20; Status=Active; date="29-10-2010"; period=>"20""","""20""",10');

        $formatter = $this->newFormatter();
        $formatter->setField('user', null, false, true);
        $formatter->setField('status');
        $formatter->setField('date');
        $formatter->setField('period', null, false, false, true);

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']   = new FilterValuesBag('user', '2,3,10-20', array(new SingleValue('2'), new SingleValue('3')), array(), array(2 => new Range('10', '20')), array(), array(), 2);
        $expectedValues['status'] = new FilterValuesBag('status', 'Active', array(new SingleValue('Active')), array(), array(), array(), array(), 0);
        $expectedValues['date']   = new FilterValuesBag('date', '"29-10-2010"', array(new SingleValue('29-10-2010')), array(), array(), array(), array(), 0);
        $expectedValues['period'] = new FilterValuesBag('period', '>"20""","""20""",10', array(1 => new SingleValue('"20"'), 2 => new SingleValue('10')), array(), array(), array(0 => new Compare('20"', '>')), array(), 2);

        $this->assertEquals($expectedValues, $filters[0]);
    }

   // Test Aliases

    function testFieldAlias()
    {
        $input = new QueryInput();
        $input->setQueryString('Gebruiker=2,3,10-20; Status=Active; datung=29.10.2010');

        $formatter = $this->newFormatter();
        $formatter->setFieldAlias('user', 'gebruiker');
        $formatter->setFieldAlias('date', array('datum', 'datung'));

        $formatter->setField('user', null, true, true);
        $formatter->setField('status', null, true, true);
        $formatter->setField('date', null, true, true);

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']   = new FilterValuesBag('gebruiker', '2,3,10-20', array(new SingleValue('2'), new SingleValue('3')), array(), array(2 => new Range('10', '20')), array(), array(), 2);
        $expectedValues['status'] = new FilterValuesBag('status', 'Active', array(new SingleValue('Active')), array(), array(), array(), array(), 0);
        $expectedValues['date']   = new FilterValuesBag('datung', '29.10.2010', array(new SingleValue('29.10.2010')), array(), array(), array(), array(), 0);

        $this->assertEquals($expectedValues, $filters[0]);
    }

    function testFieldAliasByTranslator()
    {
        $input = new QueryInput();
        $input->setQueryString('Gebruiker=2,3,10-20; Status=Active; datung=29.10.2010; periods=>20,10; cat=10');

        $this->translator->addResource('array', array('search' => array('gebruiker'    => 'user',
                                                                        'datum'        => 'date',
                                                                        'datung'       => 'date')), 'en', 'filter');

        $formatter = $this->newFormatter();
        $formatter->setFieldAliasByTranslator('search.', 'filter');

        $formatter->setFieldAlias('period', array('periods'));

        $formatter->setField('user', null, true, true);
        $formatter->setField('status', null, true, true);
        $formatter->setField('date', null, true, true);
        $formatter->setField('period', null, false, false, true);

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']   = new FilterValuesBag('gebruiker', '2,3,10-20', array(new SingleValue('2'), new SingleValue('3')), array(), array(2 => new Range('10', '20')), array(), array(), 2);
        $expectedValues['status'] = new FilterValuesBag('status', 'Active', array(new SingleValue('Active')), array(), array(), array(), array(), 0);
        $expectedValues['date']   = new FilterValuesBag('datung', '29.10.2010', array(new SingleValue('29.10.2010')), array(), array(), array(), array(), 0);
        $expectedValues['period'] = new FilterValuesBag('periods', '>20,10', array(1 => new SingleValue('10')), array(), array(), array(0 => new Compare('20', '>')), array(), 1);

        $this->assertEquals($expectedValues, $filters[0]);
    }

    function testFieldAliasMerge()
    {
        $input = new QueryInput();
        $input->setQueryString('User=2,3; Status=Active; datung=29.10.2010; datum=30.10.2010');

        $formatter = $this->newFormatter();
        $formatter->setFieldAlias('date', array('datum', 'datung'));

        $formatter->setField('user', null, true, true);
        $formatter->setField('status', null, true, true);
        $formatter->setField('date', null, true, true);

        $formatter->setFieldAlias('date', array('datum', 'datung'));

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $messages = $formatter->getMessages();

        $this->assertEquals(array("Merged 'datum' to 'datung'."), $messages['info']);

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']   = new FilterValuesBag('user', '2,3', array(new SingleValue('2'), new SingleValue('3')), array(), array(), array(), array(), 1);
        $expectedValues['status'] = new FilterValuesBag('status', 'Active', array(new SingleValue('Active')), array(), array(), array(), array(), 0);
        $expectedValues['date']   = new FilterValuesBag('datung', '29.10.2010,30.10.2010', array(new SingleValue('29.10.2010'), new SingleValue('30.10.2010')), array(), array(), array(), array(), 1);

        $this->assertEquals($expectedValues, $filters[0]);
    }

    function testFieldAliasMergeWithGroups()
    {
        $input = new QueryInput();
        $input->setQueryString('(User=2,3; Status=Active; datung=29.10.2010; datum=30.10.2010;),(User=2,3; Status=Active; datung=29.10.2011; datum=30.10.2011;)');

        $formatter = $this->newFormatter();
        $formatter->setFieldAlias('date', array('datum', 'datung'));

        $formatter->setField('user', null, true, true);
        $formatter->setField('status', null, true, true);
        $formatter->setField('date', null, true, true);

        $formatter->setFieldAlias('date', array('datum', 'datung'));

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $messages = $formatter->getMessages();

        $this->assertEquals(array("Merged 'datum' to 'datung' in group 1.", "Merged 'datum' to 'datung' in group 2."), $messages['info']);
        $this->assertTrue($formatter->hasGroups());

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

    function testFieldAliasMergeWithGroups2()
    {
        $input = new QueryInput();
        $input->setQueryString('(User=2,3; Status=Active; datung=29.10.2010; datum=30.10.2010;),(User=2,3; Status=Active; datung=29.10.2011;)');

        $formatter = $this->newFormatter();
        $formatter->setFieldAlias('date', array('datum', 'datung'));

        $formatter->setField('user', null, true, true);
        $formatter->setField('status', null, true, true);
        $formatter->setField('date', null, true, true);

        $formatter->setFieldAlias('date', array('datum', 'datung'));

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $messages = $formatter->getMessages();

        $this->assertEquals(array("Merged 'datum' to 'datung' in group 1."), $messages['info']);
        $this->assertTrue($formatter->hasGroups());

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

    function testValueMatcher()
    {
        $input = new QueryInput();
        $input->setQueryString('User=2,3,10-20; Status=Active; date=29-10-2010; period=>20,10');

        $formatter = $this->newFormatter();
        $formatter->setField('user', null, false, true);
        $formatter->setField('status');
        $formatter->setField('date', new Date());
        $formatter->setField('period', null, false, false, true);

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']   = new FilterValuesBag('user', '2,3,10-20', array(new SingleValue('2'), new SingleValue('3')), array(), array(2 => new Range('10', '20')), array(), array(), 2);
        $expectedValues['status'] = new FilterValuesBag('status', 'Active', array(new SingleValue('Active')), array(), array(), array(), array(), 0);
        $expectedValues['date']   = new FilterValuesBag('date', '29-10-2010', array(new SingleValue('2010-10-29', '29-10-2010')), array(), array(), array(), array(), 0);
        $expectedValues['period'] = new FilterValuesBag('period', '>20,10', array(1 => new SingleValue('10')), array(), array(), array(0 => new Compare('20', '>')), array(), 1);

        $this->assertEquals($expectedValues, $filters[0]);
    }

    function testValueMatcher2()
    {
        $input = new QueryInput();
        $input->setQueryString('User=2,3,10-20; invoice=F2010-48932,F2011-48932-F2012-48932; date=29-10.2010; period=>20,10');

        $formatter = $this->newFormatter();
        $formatter->setField('user', null, false, true);
        $formatter->setField('invoice', new InvoiceType(), false, true);
        $formatter->setField('date', new Date());
        $formatter->setField('period', null, false, false, true);

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']    = new FilterValuesBag('user', '2,3,10-20', array(new SingleValue('2'), new SingleValue('3')), array(), array(2 => new Range('10', '20')), array(), array(), 2);
        $expectedValues['invoice'] = new FilterValuesBag('invoice', 'F2010-48932,F2011-48932-F2012-48932', array(new SingleValue('F2010-48932')), array(), array(1 => new Range('F2011-48932', 'F2012-48932')), array(), array(), 1);
        $expectedValues['date']    = new FilterValuesBag('date', '29-10.2010', array(new SingleValue('2010-10-29', '29-10.2010')), array(), array(), array(), array(), 0);
        $expectedValues['period']  = new FilterValuesBag('period', '>20,10', array(1 => new SingleValue('10')), array(), array(), array(0 => new Compare('20', '>')), array(), 1);

        $this->assertEquals($expectedValues, $filters[0]);
    }

    function testValueMatcherWithRange()
    {
        $input = new QueryInput();
        $input->setQueryString('User=2,3,10-20; Status=Active; date=29-10-2010; period=>20,10');

        $formatter = $this->newFormatter();
        $formatter->setField('user', null, false, true);
        $formatter->setField('status');
        $formatter->setField('date', new Date());
        $formatter->setField('period', null, false, false, true);

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']   = new FilterValuesBag('user', '2,3,10-20', array(new SingleValue('2'), new SingleValue('3')), array(), array(2 => new Range('10', '20')), array(), array(), 2);
        $expectedValues['status'] = new FilterValuesBag('status', 'Active', array(new SingleValue('Active')), array(), array(), array(), array(), 0);
        $expectedValues['date']   = new FilterValuesBag('date', '29-10-2010', array(new SingleValue('2010-10-29', '29-10-2010')), array(), array(), array(), array(), 0);
        $expectedValues['period'] = new FilterValuesBag('period', '>20,10', array(1 => new SingleValue('10')), array(), array(), array(0 => new Compare('20', '>')), array(), 1);

        $this->assertEquals($expectedValues, $filters[0]);
    }

    // Registry

    function testRegistry()
    {
        $input = new QueryInput();
        $input->setQueryString('User=2; Status=Active; date=29.10.2010');

        $formatter = $this->newFormatter(false);

        $oRegistry = new ModifiersRegistry();
        $oRegistry->registerPostModifier(new Validator());
        $formatter->setModifiersRegistry($oRegistry);

        $this->assertEquals($formatter->getModifiersRegistry(), $oRegistry);

        $formatter->setField('period', new Date(), true);
        $formatter->setField('User', null, true);

        $this->assertFalse($formatter->formatInput($input));

        $messages = $formatter->getMessages();
        $this->assertEquals(array('Field \'period\' is required.'), $messages['error']);
    }

    function testRegistryGetModifiers()
    {
        $oValidator = new Validator();

        $oRegistry = new ModifiersRegistry();
        $oRegistry->registerPostModifier($oValidator);

        $this->assertEquals(array('validator' => $oValidator), $oRegistry->getPostModifiers());
    }

    function testRegistryGetFromFormatterEmpty()
    {
        $formatter = $this->newFormatter(false);

        $this->assertFalse($formatter->hasModifiersRegistry());
        $this->assertInstanceOf('Rollerworks\\RecordFilterBundle\\Formatter\\ModifiersRegistry', $formatter->getModifiersRegistry(true));
        $this->assertTrue($formatter->hasModifiersRegistry());
    }

    function testRegistryGetFromFormatterEmptyError()
    {
        $formatter = $this->newFormatter(false);

        $this->setExpectedException('RuntimeException', 'No ModifiersRegistry instance registered.');
        $formatter->getModifiersRegistry();
    }

    // Test to string

    function testToString()
    {
        $input = new QueryInput();
        $input->setQueryString('Gebruiker=2,3,10-20; Status=Active; datung=29.10.2010');

        $formatter = $this->newFormatter();

        $formatter->setFieldAlias('user', 'gebruiker');
        $formatter->setFieldAlias('date', array('datum', 'datung'));

        $formatter->setField('user', null, true, true);
        $formatter->setField('status', null, true, true);
        $formatter->setField('date', new Date(), true, true);

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $this->assertEquals('gebruiker=2,3,10-20; status=Active; datung=29.10.2010;', $formatter->__toString());
        $this->assertEquals('gebruiker=2,3,10-20; status=Active; datung=29.10.2010;', $formatter->__toString());
    }

    function testToStringNoValidationPerformed()
    {
        $input = new QueryInput();
        $input->setQueryString('Gebruiker=2,3,10-20; Status=Active; datung=29.10.2010');

        $formatter = $this->newFormatter();

        $formatter->setFieldAlias('user', 'gebruiker');
        $formatter->setFieldAlias('date', array('datum', 'datung'));

        $formatter->setField('user', null, true, true);
        $formatter->setField('status', null, true, true);
        $formatter->setField('date', new Date(), true, true);

        $this->assertNull($formatter->__toString());
    }

    function testToStringWithGroups()
    {
        $input = new QueryInput();
        $input->setQueryString('(Gebruiker=2,3,10-20; Status=Active; datung=29.10.2010;),(Gebruiker=2,5,15-20; Status=Active; datung=30.10.2011;)');

        $formatter = $this->newFormatter();

        $formatter->setFieldAlias('user', 'gebruiker');
        $formatter->setFieldAlias('date', array('datum', 'datung'));

        $formatter->setField('user', null, true, true);
        $formatter->setField('status', null, true, true);
        $formatter->setField('date', new Date(), true, true);

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $this->assertEquals('( gebruiker=2,3,10-20; status=Active; datung=29.10.2010; ), ( gebruiker=2,5,15-20; status=Active; datung=30.10.2011; )', $formatter->__toString());
        $this->assertEquals('( gebruiker=2,3,10-20; status=Active; datung=29.10.2010; ), ( gebruiker=2,5,15-20; status=Active; datung=30.10.2011; )', $formatter->__toString());
    }

    // Test failures

    function testFieldAliasByTranslatorInValidPrefix()
    {
        $formatter = $this->newFormatter();

        $this->setExpectedException('\InvalidArgumentException', 'Prefix must be an string and can not be empty');

        $formatter->setFieldAliasByTranslator(false);
    }

    function testFieldAliasByTranslatorInValidDomain()
    {
        $formatter = $this->newFormatter();

        $this->setExpectedException('\InvalidArgumentException', 'Domain must be an string and can not be empty');

        $formatter->setFieldAliasByTranslator('t.', false);
    }

    function testValidateNoValidations()
    {
        $formatter = $this->newFormatter();

        $this->setExpectedException('\RuntimeException', 'Formatter::getFilters(): No fields are registered.');

        $formatter->getFilters();
    }

    function testGetFilterNoValidations()
    {
        $input = new QueryInput();
        $input->setQueryString('(Gebruiker=2,3,10-20; Status=Active; datung=29.10.2010),(Gebruiker=2,5,15-20; Status=Active; datung=30.10.2011)');

        $formatter = $this->newFormatter();

        $this->setExpectedException('\RuntimeException', 'Formatter::formatInput(): No fields registered.');
        $formatter->formatInput($input);
    }

    function testGetFilterNoValidationPerformed()
    {
        $formatter = $this->newFormatter();
        $formatter->setField('user', null, true, true);

        $this->setExpectedException('\RuntimeException', 'Formatter::getFilters(): formatInput() must be executed first.');

        $formatter->getFilters();
    }

    function testAddFieldAcceptRangesNotBoolean()
    {
        $formatter = $this->newFormatter();
        $this->setExpectedException('\InvalidArgumentException', 'Formatter::setField(): $acceptRanges must be an boolean');

        $formatter->setField('user', null, false, -1);
    }

    function testAddFieldAcceptComparesNotBoolean()
    {
        $formatter = $this->newFormatter();
        $this->setExpectedException('\InvalidArgumentException', 'Formatter::setField(): $acceptCompares must be an boolean');

        $formatter->setField('user', null, false, false, -1);
    }

    function testAddFieldReqNotBoolean()
    {
        $formatter = $this->newFormatter();
        $this->setExpectedException('\InvalidArgumentException', 'Formatter::setField(): $required must be an boolean');

        $formatter->setField('user', null, -1);
    }
}