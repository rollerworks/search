<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Rollerscapes
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @link    http://projects.rollerscapes.net/RollerFramework
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */

namespace Rollerworks\RecordFilterBundle\Tests\Modifier;

use Rollerworks\RecordFilterBundle\FilterStruct;
use Rollerworks\RecordFilterBundle\Formatter\Formatter;
use Rollerworks\RecordFilterBundle\Formatter\ModifiersRegistry;

use Rollerworks\RecordFilterBundle\Formatter\Modifier\DuplicateRemove;
use Rollerworks\RecordFilterBundle\Formatter\Type\Date;
use Rollerworks\RecordFilterBundle\Formatter\Type\DateTime;
use Rollerworks\RecordFilterBundle\Formatter\Type\Decimal;
use Rollerworks\RecordFilterBundle\Formatter\Type\Number;
use Rollerworks\RecordFilterBundle\Input\Query as QueryInput;
use Rollerworks\RecordFilterBundle\Struct\Compare;
use Rollerworks\RecordFilterBundle\Struct\Range;
use Rollerworks\RecordFilterBundle\Struct\Value;

class DuplicateTest extends TestCase
{
    // Output formatter: Duplicates

    function testDuplicates()
    {
        $input = new QueryInput();
        $input->setQueryString('User=2,3,10-20; Status=Active; date=29.10.2010,29.10.2010; period=>20,10');

        $formatter = $this->newFormatter();
        $formatter->setField('user', null, true, true);
        $formatter->setField('status', null, true, true);
        $formatter->setField('date', null, true, true);
        $formatter->setField('period', null, true, true, true);

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $messages = $formatter->getMessages();
        $this->assertEquals(array("Duplicate value '\"29.10.2010\"' in field 'date' (removed)."), $messages['info']);

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']   = new FilterStruct('user', '2,3,10-20', array(new Value('2'), new Value('3')), array(), array(2 => new Range('10', '20')));
        $expectedValues['status'] = new FilterStruct('status', 'Active', array(new Value('Active')));
        $expectedValues['date']   = new FilterStruct('date', '29.10.2010,29.10.2010', array(new Value('29.10.2010')));
        $expectedValues['period'] = new FilterStruct('period', '>20,10', array(1 => new Value('10')), array(), array(), array(0 => new Compare('20', '>')));

        $this->assertEquals($expectedValues, $filters[0]);
    }

    function testDuplicatesMore()
    {
        $input = new QueryInput();
        $input->setQueryString('User=2,3,10-20; Status=Active; date="29.10.2010",29.10.2010; period=>20,10');

        $formatter = $this->newFormatter();
        $formatter->setField('user', null, true, true);
        $formatter->setField('status', null, true, true);
        $formatter->setField('date', null, true, true);
        $formatter->setField('period', null, true, true, true);

        if (! $formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $messages = $formatter->getMessages();
        $this->assertEquals(array("Duplicate value '\"29.10.2010\"' in field 'date' (removed)."), $messages['info']);

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']   = new FilterStruct('user', '2,3,10-20', array(new Value('2'), new Value('3')), array(), array(2 => new Range('10', '20')));
        $expectedValues['status'] = new FilterStruct('status', 'Active', array(new Value('Active')));
        $expectedValues['date']   = new FilterStruct('date', '"29.10.2010",29.10.2010', array(new Value('29.10.2010')));
        $expectedValues['period'] = new FilterStruct('period', '>20,10', array(1 => new Value('10')), array(), array(), array(0 => new Compare('20', '>')));

        $this->assertEquals($expectedValues, $filters[0]);
    }

    function testDuplicatesWithType()
    {
        $input = new QueryInput();
        $input->setQueryString('User=2,3,10-20; Status=Active; date="29.10.2010","29-10-2010",29.10.2010');

        $formatter = $this->newFormatter();
        $formatter->setField('user', null, true, true);
        $formatter->setField('status', null, true, true);
        $formatter->setField('date', new Date(), true, true);

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $messages = $formatter->getMessages();
        $this->assertEquals(array("Duplicate value '\"29-10-2010\"' in field 'date' (removed).", "Duplicate value '\"29.10.2010\"' in field 'date' (removed)."), $messages['info']);

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']   = new FilterStruct('user', '2,3,10-20', array(new Value('2'), new Value('3')), array(), array(2 => new Range('10', '20')));
        $expectedValues['status'] = new FilterStruct('status', 'Active', array(new Value('Active')));
        $expectedValues['date']   = new FilterStruct('date', '"29.10.2010","29-10-2010",29.10.2010', array(new Value('2010-10-29', '29.10.2010')));

        $this->assertEquals($expectedValues, $filters[0]);
    }

    function testDuplicatesWithTypeAndRange()
    {
        $input = new QueryInput();
        $input->setQueryString('User=2,3,10-20; Status=Active; date=29.10.2010,29.10.2010,"29.10.2010"-"10.12.2010","29-10-2010"-10.12.2010,"29.10.2010"-"10.12.2010"');

        $formatter = $this->newFormatter();
        $formatter->setField('user', null, true, true);
        $formatter->setField('status', null, true, true);
        $formatter->setField('date', new Date(), true, true);

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $messages = $formatter->getMessages();
        $this->assertEquals(array(
            "Duplicate value '\"29.10.2010\"' in field 'date' (removed).",
            "Duplicate value '\"29-10-2010\"-\"10.12.2010\"' in field 'date' (removed).",
            "Duplicate value '\"29.10.2010\"-\"10.12.2010\"' in field 'date' (removed)."), $messages['info']);

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']   = new FilterStruct('user', '2,3,10-20', array(new Value('2'), new Value('3')), array(), array(2 => new Range('10', '20')));
        $expectedValues['status'] = new FilterStruct('status', 'Active', array(new Value('Active')));
        $expectedValues['date']   = new FilterStruct('date', '29.10.2010,29.10.2010,"29.10.2010"-"10.12.2010","29-10-2010"-10.12.2010,"29.10.2010"-"10.12.2010"', array(new Value('2010-10-29', '29.10.2010')), array(), array(2 => new Range('2010-10-29', '2010-12-10', '29.10.2010', '10.12.2010')));

        $this->assertEquals($expectedValues, $filters[0]);
    }

    // For clarity an connected range is like 10-20,20-30 -> 10-30
    // The higher-value is equal to an other lower-value
    function testDuplicatesWithTypeAndConnectedRange()
    {
        $input = new QueryInput();
        $input->setQueryString('User=2,3,10-20; Status=Active; date=29.10.2010,29.10.2010, "29.10.2010"-"10.12.2010", "29-10-2010"-10.12.2010, "29.10.2010"-"10.12.2010","10-12-2010"-10.01.2011');

        $formatter = $this->newFormatter();
        $formatter->setField('user', null, true, true);
        $formatter->setField('status', null, true, true);
        $formatter->setField('date', new Date(), true, true);

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $messages = $formatter->getMessages();
        $this->assertEquals(array(
            "Duplicate value '\"29.10.2010\"' in field 'date' (removed).",
            "Duplicate value '\"29-10-2010\"-\"10.12.2010\"' in field 'date' (removed).",
            "Duplicate value '\"29.10.2010\"-\"10.12.2010\"' in field 'date' (removed).",

            "Range higher-value of '\"29.10.2010\"-\"10.12.2010\"' equals lower-value of range '\"10-12-2010\"-\"10.01.2011\"' in field 'date' (ranges merged to '\"29.10.2010\"-\"10.01.2011\"').",
        ), $messages['info']);

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']   = new FilterStruct('user', '2,3,10-20', array(new Value('2'), new Value('3')), array(), array(2 => new Range('10', '20')));
        $expectedValues['status'] = new FilterStruct('status', 'Active', array(new Value('Active')));
        $expectedValues['date']   = new FilterStruct('date', '29.10.2010,29.10.2010, "29.10.2010"-"10.12.2010", "29-10-2010"-10.12.2010, "29.10.2010"-"10.12.2010","10-12-2010"-10.01.2011', array(new Value('2010-10-29', '29.10.2010')), array(), array(2 => new Range('2010-10-29', '2011-01-10', '29.10.2010', '10.12.2010')));

        $this->assertEquals($expectedValues, $filters[0]);
    }

    // Make sure the connected ranges get merged, even when followed by duplicates
    function testDuplicatesWithTypeAndConnectedRange2()
    {
        $input = new QueryInput();
        $input->setQueryString('User=2,3,10-20; Status=Active; date=29.10.2010,29.10.2010, "29-10-2010"-10.12.2010, "29.10.2010"-"10.12.2010","10-12-2010"-10.01.2011, "29.10.2010"-"10.12.2010"');

        $formatter = $this->newFormatter();
        $formatter->setField('user', null, true, true);
        $formatter->setField('status', null, true, true);
        $formatter->setField('date', new Date(), true, true);

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $messages = $formatter->getMessages();
        $this->assertEquals(array(
            "Duplicate value '\"29.10.2010\"' in field 'date' (removed).",
            "Duplicate value '\"29.10.2010\"-\"10.12.2010\"' in field 'date' (removed).",
            "Duplicate value '\"29.10.2010\"-\"10.12.2010\"' in field 'date' (removed).",

            "Range higher-value of '\"29-10-2010\"-\"10.12.2010\"' equals lower-value of range '\"10-12-2010\"-\"10.01.2011\"' in field 'date' (ranges merged to '\"29-10-2010\"-\"10.01.2011\"').",
        ), $messages['info']);

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']   = new FilterStruct('user', '2,3,10-20', array(new Value('2'), new Value('3')), array(), array(2 => new Range('10', '20')));
        $expectedValues['status'] = new FilterStruct('status', 'Active', array(new Value('Active')));
        $expectedValues['date']   = new FilterStruct('date', '29.10.2010,29.10.2010, "29-10-2010"-10.12.2010, "29.10.2010"-"10.12.2010","10-12-2010"-10.01.2011, "29.10.2010"-"10.12.2010"', array(new Value('2010-10-29', '29.10.2010')), array(), array(2 => new Range('2010-10-29', '2011-01-10', '29-10-2010', '10.12.2010')));

        $this->assertEquals($expectedValues, $filters[0]);
    }

    // Make sure the connected ranges get merged, even when followed by overlapping ranges
    function testDuplicatesWithTypeAndConnectedRange3()
    {
        $input = new QueryInput();
        $input->setQueryString('User=2,3,10-20; Status=Active; date="10-12-2010"-10.01.2011, "29.10.2010"-"10.12.2010", "30.10.2010"-"08.12.2010"');//"30-10-2010"-01.01.2011

        $formatter = $this->newFormatter();
        $formatter->setField('user', null, true, true);
        $formatter->setField('status', null, true, true);
        $formatter->setField('date', new Date(), true, true);

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $messages = $formatter->getMessages();
        $this->assertEquals(array(
            "Range higher-value of '\"29.10.2010\"-\"10.12.2010\"' equals lower-value of range '\"10-12-2010\"-\"10.01.2011\"' in field 'date' (ranges merged to '\"29.10.2010\"-\"10.01.2011\"').",
            "Range '\"30.10.2010\"-\"08.12.2010\"' in field 'date' is overlapping in range '\"29.10.2010\"-\"10.12.2010\"'."
        ), $messages['info']);

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']   = new FilterStruct('user', '2,3,10-20', array(new Value('2'), new Value('3')), array(), array(2 => new Range('10', '20')));
        $expectedValues['status'] = new FilterStruct('status', 'Active', array(new Value('Active')));
        $expectedValues['date']   = new FilterStruct('date', '"10-12-2010"-10.01.2011, "29.10.2010"-"10.12.2010", "30.10.2010"-"08.12.2010"', array(), array(), array(1 => new Range('2010-10-29', '2011-01-10', '29.10.2010', '10.12.2010')));

        $this->assertEquals($expectedValues, $filters[0]);
    }

    function testDuplicatesWithTypeAndCompare()
    {
        $input = new QueryInput();
        $input->setQueryString('User=2,3,10-20; Status=Active; date=25.05.2010,>25.5.2010,>"25.05.2010",<="25.05.2010","25-05-2010"');

        $formatter = $this->newFormatter();
        $formatter->setField('user', null, true, true);
        $formatter->setField('status', null, true, true);
        $formatter->setField('date', new Date(), true, true, true);

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $messages = $formatter->getMessages();
        $this->assertEquals(array("Duplicate value '\"25-05-2010\"' in field 'date' (removed).", "Duplicate value '>\"25.05.2010\"' in field 'date' (removed)."), $messages['info']);

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']   = new FilterStruct('user', '2,3,10-20', array(new Value('2'), new Value('3')), array(), array(2 => new Range('10', '20')));
        $expectedValues['status'] = new FilterStruct('status', 'Active', array(new Value('Active')));
        $expectedValues['date'] = new FilterStruct('date', '25.05.2010,>25.5.2010,>"25.05.2010",<="25.05.2010","25-05-2010"', array(new Value('2010-05-25', '25.05.2010')), array(), array(), array(1 => new Compare('2010-05-25', '>', '25.5.2010'),
                                                                                                                                                                                                    3 => new Compare('2010-05-25', '<=', '25.05.2010')));

        $this->assertEquals($expectedValues, $filters[0]);
    }

    function testDuplicatesWithTypeAndCompareGetValues()
    {
        $input = new QueryInput();
        $input->setQueryString('User=2,3,10-20; Status=Active; date=25.05.2010,>25.5.2010,>"25.05.2010",<="25.05.2010","25-05-2010"');

        $formatter = $this->newFormatter();
        $formatter->setField('user', null, true, true);
        $formatter->setField('status', null, true, true);
        $formatter->setField('date', new Date(), true, true, true);

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $messages = $formatter->getMessages();
        $this->assertEquals(array("Duplicate value '\"25-05-2010\"' in field 'date' (removed).", "Duplicate value '>\"25.05.2010\"' in field 'date' (removed)."), $messages['info']);

        $aValues = $formatter->getFiltersValues();

        $expectedValues = array();
        $expectedValues[0]['user']   = array('2', '3', '10-20');
        $expectedValues[0]['status'] = array('Active');
        $expectedValues[0]['date']   = array('25.05.2010', '>25.5.2010', 3 => '<="25.05.2010"');

        $this->assertEquals($expectedValues, $aValues);

        $this->assertEquals('user=2, 3, 10-20; status=Active; date=25.05.2010, >25.5.2010, <="25.05.2010";', $formatter->getFiltersValues(true));
        $this->assertEquals('user=2, 3, 10-20;'.PHP_EOL.'status=Active;'.PHP_EOL.'date=25.05.2010, >25.5.2010, <="25.05.2010";', $formatter->getFiltersValues(true, true));


        $input = new QueryInput();
        $input->setQueryString('
        (User=2,3,10-20; Status=Active; date=25.05.2010,>25.5.2010,>"25.05.2010",<="25.05.2010","25-05-2010";),
        (User=2,10-20; Status=Archived; date=26.05.2010,>26.5.2010,>"26.05.2010",<="26.05.2010","26-05-2010";)');

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $messages = $formatter->getMessages();
        $this->assertEquals(array(
            "Duplicate value '\"25-05-2010\"' in field 'date' (removed) in group 1.",
            "Duplicate value '>\"25.05.2010\"' in field 'date' (removed) in group 1.",
            "Duplicate value '\"26-05-2010\"' in field 'date' (removed) in group 2.",
            "Duplicate value '>\"26.05.2010\"' in field 'date' (removed) in group 2.",
        ), $messages['info']);

        $aValues = $formatter->getFiltersValues();

        $expectedValues = array();
        $expectedValues[0]['user']   = array('2', '3', '10-20');
        $expectedValues[0]['status'] = array('Active');
        $expectedValues[0]['date']   = array('25.05.2010', '>25.5.2010', 3 => '<="25.05.2010"');

        $expectedValues[1]['user']   = array('2', '10-20');
        $expectedValues[1]['status'] = array('Archived');
        $expectedValues[1]['date']   = array('26.05.2010', '>26.5.2010', 3 => '<="26.05.2010"');

        $this->assertEquals($expectedValues, $aValues);

        $this->assertEquals('( user=2, 3, 10-20; status=Active; date=25.05.2010, >25.5.2010, <="25.05.2010"; ), ( user=2, 10-20; status=Archived; date=26.05.2010, >26.5.2010, <="26.05.2010"; )', $formatter->getFiltersValues(true));
        $this->assertEquals('( user=2, 3, 10-20;'.PHP_EOL.'status=Active;'.PHP_EOL.'date=25.05.2010, >25.5.2010, <="25.05.2010"; ), ( user=2, 10-20;'.PHP_EOL.'status=Archived;'.PHP_EOL.'date=26.05.2010, >26.5.2010, <="26.05.2010"; )', $formatter->getFiltersValues(true, true));
    }

    function testRedundantCompare()
    {
        $input = new QueryInput();
        $input->setQueryString('User=2,3,10-20; Status=Active; date=>25.05.2010,>=25.05.2010');

        $formatter = $this->newFormatter();
        $formatter->setField('user', null, true, true);
        $formatter->setField('status', null, true, true);
        $formatter->setField('date', new Date(), true, true, true);

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $messages = $formatter->getMessages();
        $this->assertEquals(array("Comparison '>\"25.05.2010\"' is already covered by '>=' field 'date' (removed)."), $messages['info']);

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']   = new FilterStruct('user', '2,3,10-20', array(new Value('2'), new Value('3')), array(), array(2 => new Range('10', '20')));
        $expectedValues['status'] = new FilterStruct('status', 'Active', array(new Value('Active')));
        $expectedValues['date']   = new FilterStruct('date', '>25.05.2010,>=25.05.2010', array(), array(), array(), array(1 => new Compare('2010-05-25', '>=', '25.05.2010')));

        $this->assertEquals($expectedValues, $filters[0]);
    }

    function testDuplicatesWithTypeAndExclude()
    {
        $input = new QueryInput();
        $input->setQueryString('User=2,3,10-20,!15,!"15"; Status=Active; date=25.05.2010');

        $formatter = $this->newFormatter();
        $formatter->setField('user', new Number(), true, true);
        $formatter->setField('status', null, true, true);
        $formatter->setField('date', new Date(), true, true);

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $messages = $formatter->getMessages();
        $this->assertEquals(array("Duplicate value '!\"15\"' in field 'user' (removed)."), $messages['info']);

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']   = new FilterStruct('user', '2,3,10-20,!15,!"15"', array(new Value('2'), new Value('3')), array(3 => new Value('15')), array(2 => new Range('10', '20')));
        $expectedValues['status'] = new FilterStruct('status', 'Active', array(new Value('Active')));
        $expectedValues['date']   = new FilterStruct('date', '25.05.2010', array(new Value('2010-05-25', '25.05.2010')));

        $this->assertEquals($expectedValues, $filters[0]);
    }

    // Example: 5,1-10 will result in: 1-10
    function testDuplicatesWithRange()
    {
        $input = new QueryInput();
        $input->setQueryString('User=5,1-10; Status=Active; date=29.10.2010-29.12.2010,20.12.2010');

        $formatter = $this->newFormatter();
        $formatter->setField('user', new Number(), true, true);
        $formatter->setField('status', null, true, true);
        $formatter->setField('date', new Date(), true, true);

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $messages = $formatter->getMessages();
        $this->assertEquals(array(
            "Value '\"5\"' in field 'user' is also in range '\"1\"-\"10\"'.",
            "Value '\"20.12.2010\"' in field 'date' is also in range '\"29.10.2010\"-\"29.12.2010\"'."
        ), $messages['info']);

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']   = new FilterStruct('user', '5,1-10', array(), array(), array(1 => new Range('1', '10')));
        $expectedValues['status'] = new FilterStruct('status', 'Active', array(new Value('Active')));
        $expectedValues['date']   = new FilterStruct('date', '29.10.2010-29.12.2010,20.12.2010', array(), array(), array(new Range('2010-10-29', '2010-12-29', '29.10.2010', '29.12.2010')));

        $this->assertEquals($expectedValues, $filters[0]);
    }


    function testDuplicatesWithExcludedRange()
    {
        $input = new QueryInput();
        $input->setQueryString('User=2,3,!28,20-50,!25-30; Status=Active; date=29.10.2010; period=>20,10');

        $formatter = $this->newFormatter();
        $formatter->setField('user', new Number(), false, true);
        $formatter->setField('status');
        $formatter->setField('date');
        $formatter->setField('period', null, false, false, true);

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $messages = $formatter->getMessages();
        $this->assertEquals(array("Value '!\"28\"' in field 'user' is also in range '!\"25\"-\"30\"'."), $messages['info']);

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']   = new FilterStruct('user', '2,3,!28,20-50,!25-30', array(new Value('2'), new Value('3')), array(), array(3 => new Range('20', '50')), array(), array(4 => new Range('25', '30')));
        $expectedValues['status'] = new FilterStruct('status', 'Active', array(new Value('Active')));
        $expectedValues['date']   = new FilterStruct('date', '29.10.2010', array(new Value('29.10.2010')));
        $expectedValues['period'] = new FilterStruct('period', '>20,10', array(1 => new Value('10')), array(), array(), array(0 => new Compare('20', '>')));

        $this->assertEquals($expectedValues, $filters[0]);
    }

    function testDuplicatesWithExcludedRangeSameAsNormalRange()
    {
        $input = new QueryInput();
        $input->setQueryString('User=2,3,25-30,!25-30; Status=Active; date=29.10.2010; period=>20,10');

        $formatter = $this->newFormatter();
        $formatter->setField('user', new Number(), false, true);
        $formatter->setField('status');
        $formatter->setField('date');
        $formatter->setField('period', null, false, false, true);

        $this->assertFalse($formatter->formatInput($input));

        $messages = $formatter->getMessages();
        $this->assertEquals(array("Excluded range '!\"25\"-\"30\"' also exists as normal range in field 'user'."), $messages['error']);
    }

    // Example: 5,1-20,5-10 will result in: 1-20
    function testDuplicatesWithRangeInRange()
    {
        $input = new QueryInput();
        $input->setQueryString('User=5,1-20,5-10; Status=Active; date=29.10.2010-29.12.2010, 30.10.2010-20.12.2010');

        $formatter = $this->newFormatter();
        $formatter->setField('user', new Number(), true, true);
        $formatter->setField('status', null, true, true);
        $formatter->setField('date', new Date(), true, true);

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $messages = $formatter->getMessages();
        $this->assertEquals(array(
            "Value '\"5\"' in field 'user' is also in range '\"1\"-\"20\"'.",
            "Range '\"5\"-\"10\"' in field 'user' is overlapping in range '\"1\"-\"20\"'.",
            "Range '\"30.10.2010\"-\"20.12.2010\"' in field 'date' is overlapping in range '\"29.10.2010\"-\"29.12.2010\"'."
        ), $messages['info']);

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']   = new FilterStruct('user', '5,1-20,5-10', array(), array(), array(1 => new Range('1', '20')));
        $expectedValues['status'] = new FilterStruct('status', 'Active', array(new Value('Active')));
        $expectedValues['date']   = new FilterStruct('date', '29.10.2010-29.12.2010, 30.10.2010-20.12.2010', array(), array(), array(new Range('2010-10-29', '2010-12-29', '29.10.2010', '29.12.2010')));

        $this->assertEquals($expectedValues, $filters[0]);
    }
}