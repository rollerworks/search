<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Rollerscapes
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link    http://projects.rollerscapes.net/RollerFramework
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */

namespace Rollerworks\RecordFilterBundle\Tests;

use Rollerworks\RecordFilterBundle\Formatter\Formatter;
use Rollerworks\RecordFilterBundle\Formatter\ModifiersRegistry;
use Rollerworks\RecordFilterBundle\Formatter\Modifier\Validator;
use Rollerworks\RecordFilterBundle\Formatter\Modifier\DuplicateRemove;
use Rollerworks\RecordFilterBundle\Formatter\Modifier\RangeNormalizer;
use Rollerworks\RecordFilterBundle\Formatter\Modifier\CompareNormalizer;
use Rollerworks\RecordFilterBundle\Formatter\Modifier\ValueOptimizer;

use Rollerworks\RecordFilterBundle\Input\Query as QueryInput;

use \Rollerworks\RecordFilterBundle\FilterStruct;
use \Rollerworks\RecordFilterBundle\Struct\Compare;
use \Rollerworks\RecordFilterBundle\Struct\Range;
use \Rollerworks\RecordFilterBundle\Struct\Value;

use Rollerworks\RecordFilterBundle\Formatter\Type\Date;
use Rollerworks\RecordFilterBundle\Formatter\Type\DateTime;
use Rollerworks\RecordFilterBundle\Formatter\Type\Number;
use Rollerworks\RecordFilterBundle\Formatter\Type\Decimal;

class FormatterTest extends \Rollerworks\RecordFilterBundle\Tests\TestCase
{
    /**
     * @param bool $loadModifiers
     * @return \Rollerworks\RecordFilterBundle\Formatter\Formatter
     */
    protected function newFormatter($loadModifiers = true)
    {
        $formatter = new Formatter($this->translator);

        if ($loadModifiers) {
            $formatter->registerPostModifier(new Validator());
            $formatter->registerPostModifier(new DuplicateRemove());
            $formatter->registerPostModifier(new RangeNormalizer());
            $formatter->registerPostModifier(new CompareNormalizer());
            $formatter->registerPostModifier(new ValueOptimizer());
        }

        return $formatter;
    }

    // Validation

    function testValidationReq()
    {
        $input = new QueryInput();
        $input->setQueryString('User=2; Status=Active; date=29.10.2010');

        $formatter = $this->newFormatter();
        $formatter->setField('period', new Date(), true);
        $formatter->setField('User', null, true);

        $this->assertFalse($formatter->formatInput($input));

        $messages = $formatter->getMessages();
        $this->assertEquals(array('Field \'period\' is required.'), $messages['error']);
    }

    function testValidationReqEmptyField()
    {
        $input = new QueryInput();
        $input->setQueryString('User=2; Status=Active; date=29.10.2010; period=,;');

        $formatter = $this->newFormatter();
        $formatter->setField('period', new Date(), true);
        $formatter->setField('User', null, true);

        $this->assertFalse($formatter->formatInput($input));

        $messages = $formatter->getMessages();
        $this->assertEquals(array('Field \'period\' is required.'), $messages['error']);
    }

    function testValidationEmptyField()
    {
        $input = new QueryInput();
        $input->setQueryString('User=2; Status=Active; date=29.10.2010; period=,;');

        $formatter = $this->newFormatter();
        $formatter->setField('period');
        $formatter->setField('User');

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $messages = $formatter->getMessages();
        $this->assertEquals(array('Failed to parse of values of \'period\', possible syntax error.'), $messages['info']);
    }

    function testValidationFail()
    {
        $input = new QueryInput();
        $input->setQueryString('User=2; Status=Active; period=2910.2010');

        $formatter = $this->newFormatter();
        $formatter->setField('period', new Date(), false, true);
        $formatter->setField('User', null, false, true);

        $this->assertFalse($formatter->formatInput($input));

        $messages = $formatter->getMessages();
        $this->assertEquals(array('Validation error(s) in field \'period\' at value \'2910.2010\': This value is not a valid date'), $messages['error']);
    }

    function testValidationFailInGroup()
    {
        $input = new QueryInput();
        $input->setQueryString('(User=2; Status=Active; period=2910.2010;),(User=2; Status=Active; period=2910.2010;)');

        $formatter = $this->newFormatter();
        $formatter->setField('period', new Date(), false, true);
        $formatter->setField('User', null, false, true);

        $this->assertFalse($formatter->formatInput($input));

        $messages = $formatter->getMessages();
        $this->assertEquals(array('Validation error(s) in field \'period\' at value \'2910.2010\' in group 1: This value is not a valid date'), $messages['error']);
    }

    function testValidationFailInGroupNoResult()
    {
        $input = new QueryInput();
        $input->setQueryString('(User=2; Status=Active; period=2910.2010;),(User=2; Status=Active; period=29.10.2010;)');

        $formatter = $this->newFormatter();
        $formatter->setField('period', new Date(), false, true);
        $formatter->setField('User', null, false, true);

        $this->assertFalse($formatter->formatInput($input));

        $messages = $formatter->getMessages();
        $this->assertEquals(array('Validation error(s) in field \'period\' at value \'2910.2010\' in group 1: This value is not a valid date'), $messages['error']);

        $this->setExpectedException('\RuntimeException', 'Formatter::getFilters(): formatInput() must be executed first.');
        $formatter->getFilters();
    }

    function testValidationFaiInlRange()
    {
        $input = new QueryInput();
        $input->setQueryString('User=2; Status=Active; period=25.10.2010-3110.2010');

        $formatter = $this->newFormatter();
        $formatter->setField('period', new Date(), true, true);
        $formatter->setField('User', null, false, true);

        $this->assertFalse($formatter->formatInput($input));

        $messages = $formatter->getMessages();
        $this->assertEquals(array('Validation error(s) in field \'period\' at value \'25.10.2010-3110.2010\': This value is not a valid date'), $messages['error']);
    }

    function testValidationFaiInlRange2()
    {
        $input = new QueryInput();
        $input->setQueryString('User=2; Status=Active; period=2510.2010-3110.2010');

        $formatter = $this->newFormatter();
        $formatter->setField('period', new Date(), true, true);
        $formatter->setField('User', null, false, true);

        $this->assertFalse($formatter->formatInput($input));

        $messages = $formatter->getMessages();
        $this->assertEquals(array('Validation error(s) in field \'period\' at value \'2510.2010-3110.2010\': This value is not a valid date'), $messages['error']);
    }

    // Validation:Range

    function testValidationRangeNotLower()
    {
        $input = new QueryInput();
        $input->setQueryString('User=2; Status=Active; period=31.10.2010-25.10.2010');

        $formatter = $this->newFormatter();
        $formatter->setField('period', new Date(), true, true);
        $formatter->setField('User', null, false, true);

        $this->assertFalse($formatter->formatInput($input));

        $messages = $formatter->getMessages();
        $this->assertEquals(array('Validation error in field \'period\': \'31.10.2010\' is not lower then \'25.10.2010\''), $messages['error']);
    }

    function testValidationFaiInCompare()
    {
        $input = new QueryInput();
        $input->setQueryString('User=2; Status=Active; period=<10.10.2010,>3110.2010');

        $formatter = $this->newFormatter();
        $formatter->setField('period', new Date(), true, true, true);
        $formatter->setField('User', null, false, true);

        $this->assertFalse($formatter->formatInput($input));

        $messages = $formatter->getMessages();
        $this->assertEquals(array('Validation error(s) in field \'period\' at value \'>3110.2010\': This value is not a valid date'), $messages['error']);
    }

    function testValidationNoRange()
    {
        $input = new QueryInput();
        $input->setQueryString('User=2-5; Status=Active; date=29.10.2010');

        $formatter = $this->newFormatter();
        $formatter->setField('User', null, true);

        $this->assertFalse($formatter->formatInput($input));

        $messages = $formatter->getMessages();
        $this->assertEquals(array('Field \'user\' does not accept ranges.'), $messages['error']);
    }

    function testValidationNoCompare()
    {
        $input = new QueryInput();
        $input->setQueryString('User=2,3,10-20; Status=Active; date=25.05.2010,>25.5.2010');

        $formatter = $this->newFormatter();
        $formatter->setField('user', null, true, true);
        $formatter->setField('status', null, true, true);
        $formatter->setField('date', new Date(), true, true);

        $this->assertFalse($formatter->formatInput($input));

        $messages = $formatter->getMessages();
        $this->assertEquals(array('Field \'date\' does not accept comparisons.'), $messages['error']);
    }

    function testValidationFaiInExclude()
    {
        $input = new QueryInput();
        $input->setQueryString('User=2; Status=Active; period=10.10.2010,!3110.2010');

        $formatter = $this->newFormatter();
        $formatter->setField('period', new Date(), true, true);
        $formatter->setField('User', null, false, true);

        $this->assertFalse($formatter->formatInput($input));

        $messages = $formatter->getMessages();
        $this->assertEquals(array('Validation error(s) in field \'period\' at value \'!3110.2010\': This value is not a valid date'), $messages['error']);
    }

    function testValidationExcludeInInclude()
    {
        $input = new QueryInput();
        $input->setQueryString('User=2; Status=Active; period=10.10.2010,!31.10.2010,31.10.2010');

        $formatter = $this->newFormatter();
        $formatter->setField('period', new Date(), true, true);
        $formatter->setField('User', null, false, true);

        $this->assertFalse($formatter->formatInput($input));

        $messages = $formatter->getMessages();
        $this->assertEquals(array('Value \'!31.10.2010\' in field \'period\' is already marked as included and can\'t be excluded.'), $messages['error']);
    }

    function testValidationIncludeInExclude()
    {
        $input = new QueryInput();
        $input->setQueryString('User=2; Status=Active; period=10.10.2010,31.10.2010,!31.10.2010');

        $formatter = $this->newFormatter();
        $formatter->setField('period', new Date(), true, true);
        $formatter->setField('User', null, false, true);

        $this->assertFalse($formatter->formatInput($input));

        $messages = $formatter->getMessages();
        $this->assertEquals(array('Value \'!31.10.2010\' in field \'period\' is already marked as included and can\'t be excluded.'), $messages['error']);
    }

    function testNoValidation()
    {
        $input = new QueryInput();
        $input->setQueryString('User=2; Status=Active; period=29.10.2010');

        $formatter = $this->newFormatter();
        $formatter->setField('period', new Date(), false, true);

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }
    }

    // Test to make sure there are no duplicate warning messages
    function testValidationInlRangeNoValidation()
    {
        $input = new QueryInput();
        $input->setQueryString('User=2-5,8-10; Status=Active; period=25.10.2010-31.10.2010,25.10.2011-31.10.2011');

        $formatter = $this->newFormatter();
        $formatter->setField('period', null, true, true);
        $formatter->setField('User', null, true, true);

        $this->assertTrue($formatter->formatInput($input));
    }

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
        $expectedValues['user']   = new FilterStruct('user', '2,3,10-"20"', array(new Value('2'), new Value('3')), array(), array(2 => new Range('10', '20')));
        $expectedValues['status'] = new FilterStruct('status', 'Active', array(new Value('Active')));
        $expectedValues['period'] = new FilterStruct('period', '29.10.2010', array(new Value('29.10.2010')));

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
        $expectedValues['user']   = new FilterStruct('user', '2,3,10-20', array(new Value('2'), new Value('3')), array(), array(2 => new Range('10', '20')));
        $expectedValues['status'] = new FilterStruct('status', 'Active', array(new Value('Active')));
        $expectedValues['date']   = new FilterStruct('date', '29.10.2010', array(new Value('29.10.2010')));
        $expectedValues['period'] = new FilterStruct('period', '>20,10', array(1 => new Value('10')), array(), array(), array(0 => new Compare('20', '>')));

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
        $input->setQueryString('User=2,3,20-10; Status=Active; date=29.10.2010; period=>20,10');

        $formatter = $this->newFormatter();
        $formatter->setField('user', new Number(), false, true);
        $formatter->setField('status');
        $formatter->setField('date');
        $formatter->setField('period', null, false, false, true);
        $this->assertFalse($formatter->formatInput($input));

        $messages = $formatter->getMessages();

        $this->assertEquals(array("Validation error in field 'user': '20' is not lower then '10'"),  $messages['error']);

        $input = new QueryInput();
        $input->setQueryString('User=2,3,10-20; Status=Active; date=29.10.2010; period=>20,10');

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']   = new FilterStruct('user', '2,3,10-20', array(new Value('2'), new Value('3')), array(), array(2 => new Range('10', '20')));
        $expectedValues['status'] = new FilterStruct('status', 'Active', array(new Value('Active')));
        $expectedValues['date']   = new FilterStruct('date', '29.10.2010', array(new Value('29.10.2010')));
        $expectedValues['period'] = new FilterStruct('period', '>20,10', array(1 => new Value('10')), array(), array(), array(0 => new Compare('20', '>')));

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
        $expectedValues['user'] = new FilterStruct('user', '2,3,10-20,!15', array(new Value('2'), new Value('3')), array(3 => new Value('15')), array(2 => new Range('10', '20')));
        $expectedValues['status'] = new FilterStruct('status', 'Active', array(new Value('Active')));
        $expectedValues['date'] = new FilterStruct('date', '29.10.2010', array(new Value('29.10.2010')));
        $expectedValues['period'] = new FilterStruct('period', '>20,10', array(1 => new Value('10')), array(), array(), array(0 => new Compare('20', '>')));

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
        $expectedValues['user']   = new FilterStruct('user', '2,3,20-50,!25-30', array(new Value('2'), new Value('3')), array(), array(2 => new Range('20', '50')), array(), array(3 => new Range('25', '30')));
        $expectedValues['status'] = new FilterStruct('status', 'Active', array(new Value('Active')));
        $expectedValues['date']   = new FilterStruct('date', '29.10.2010', array(new Value('29.10.2010')));
        $expectedValues['period'] = new FilterStruct('period', '>20,10', array(1 => new Value('10')), array(), array(), array(0 => new Compare('20', '>')));

        $this->assertEquals($expectedValues, $filters[0]);
    }

    function testGetFiltersEmptyFieldAndValue()
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
        $expectedValues['user']   = new FilterStruct('user', '2,,3,10-20', array(new Value('2'), new Value('3')), array(), array(2 => new Range('10', '20')));
        $expectedValues['status'] = new FilterStruct('status', 'Active', array(new Value('Active')));
        $expectedValues['date']   = new FilterStruct('date', '29.10.2010', array(new Value('29.10.2010')));

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
        $expectedValues['user']   = new FilterStruct('user', '2,3,10-20', array(new Value('2'), new Value('3')), array(), array(2 => new Range('10', '20')));
        $expectedValues['status'] = new FilterStruct('status', 'Active', array(new Value('Active')));
        $expectedValues['date']   = new FilterStruct('date', '"29-10-2010"', array(new Value('29-10-2010')));
        $expectedValues['period'] = new FilterStruct('period', '>"20""","""20""",10', array(1 => new Value('"20"'), 2 => new Value('10')), array(), array(), array(0 => new Compare('20"', '>')));

        $this->assertEquals($expectedValues, $filters[0]);
    }

    // Output formatter: Duplicates

    function testFormatterDuplicates()
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

    function testFormatterDuplicatesMore()
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

    function testFormatterDuplicatesWithType()
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

    function testFormatterDuplicatesWithTypeAndRange()
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
    function testFormatterDuplicatesWithTypeAndConnectedRange()
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
    function testFormatterDuplicatesWithTypeAndConnectedRange2()
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
    function testFormatterDuplicatesWithTypeAndConnectedRange3()
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

    function testFormatterDuplicatesWithTypeAndCompare()
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

    function testFormatterDuplicatesWithTypeAndCompareGetValues()
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

    function testFormatterRedundantCompare()
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

    function testFormatterDuplicatesWithTypeAndExclude()
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
    function testFormatterDuplicatesWithRange()
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


    function testFormatterDuplicatesWithExcludedRange()
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

    function testFormatterDuplicatesWithExcludedRangeSameAsNormalRange()
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
    function testFormatterDuplicatesWithRangeInRange()
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

    // Test optimize value

    function testOptimizeValue()
    {
        $input = new QueryInput();
        $input->setQueryString('User=2,3,10-20; Status=Active,"Not-active",Removed; date=29.10.2010; period=>20,10');

        $formatter = $this->newFormatter();
        $formatter->setField('user',  new Number(), false, true);
        $formatter->setField('status', new StatusType());
        $formatter->setField('date');
        $formatter->setField('period', null, false, false, true);

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']   = new FilterStruct('user', '2,3,10-20', array(new Value('2'), new Value('3')), array(), array(2 => new Range('10', '20')));
        $expectedValues['date']   = new FilterStruct('date', '29.10.2010', array(new Value('29.10.2010')));
        $expectedValues['period'] = new FilterStruct('period', '>20,10', array(1 => new Value('10')), array(), array(), array(0 => new Compare('20', '>')));

        $this->assertEquals($expectedValues, $filters[0]);
    }

    function testOptimizeValueNoOptimize()
    {
        $input = new QueryInput();
        $input->setQueryString('User=2,3,10-20; Status=Active,"Not-active",Removed; date=29.10.2010; period=>20,10');

        $formatter = $this->newFormatter();
        $formatter->setField('user', null, false, true);
        $formatter->setField('status', new StatusType());
        $formatter->setField('date');
        $formatter->setField('period', null, false, false, true);

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']   = new FilterStruct('user', '2,3,10-20', array(new Value('2'), new Value('3')), array(), array(2 => new Range('10', '20')));
        $expectedValues['date']   = new FilterStruct('date', '29.10.2010', array(new Value('29.10.2010')));
        $expectedValues['period'] = new FilterStruct('period', '>20,10', array(1 => new Value('10')), array(), array(), array(0 => new Compare('20', '>')));

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
        $expectedValues['user']   = new FilterStruct('gebruiker', '2,3,10-20', array(new Value('2'), new Value('3')), array(), array(2 => new Range('10', '20')));
        $expectedValues['status'] = new FilterStruct('status', 'Active', array(new Value('Active')));
        $expectedValues['date']   = new FilterStruct('datung', '29.10.2010', array(new Value('29.10.2010')));

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
        $expectedValues['user']   = new FilterStruct('gebruiker', '2,3,10-20', array(new Value('2'), new Value('3')), array(), array(2 => new Range('10', '20')));
        $expectedValues['status'] = new FilterStruct('status', 'Active', array(new Value('Active')));
        $expectedValues['date']   = new FilterStruct('datung', '29.10.2010', array(new Value('29.10.2010')));
        $expectedValues['period'] = new FilterStruct('periods', '>20,10', array(1 => new Value('10')), array(), array(), array(0 => new Compare('20', '>')));

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
        $expectedValues['user']   = new FilterStruct('user', '2,3', array(new Value('2'), new Value('3')));
        $expectedValues['status'] = new FilterStruct('status', 'Active', array(new Value('Active')));
        $expectedValues['date']   = new FilterStruct('datung', '29.10.2010,30.10.2010', array(new Value('29.10.2010'), new Value('30.10.2010')));

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
        $expectedValues[0]['user']   = new FilterStruct('user', '2,3', array(new Value('2'), new Value('3')));
        $expectedValues[0]['status'] = new FilterStruct('status', 'Active', array(new Value('Active')));
        $expectedValues[0]['date']   = new FilterStruct('datung', '29.10.2010,30.10.2010', array(new Value('29.10.2010'), new Value('30.10.2010')));

        $expectedValues[1]['user']   = new FilterStruct('user', '2,3', array(new Value('2'), new Value('3')));
        $expectedValues[1]['status'] = new FilterStruct('status', 'Active', array(new Value('Active')));
        $expectedValues[1]['date']   = new FilterStruct('datung', '29.10.2011,30.10.2011', array(new Value('29.10.2011'), new Value('30.10.2011')));

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
        $expectedValues[0]['user']   = new FilterStruct('user', '2,3', array(new Value('2'), new Value('3')));
        $expectedValues[0]['status'] = new FilterStruct('status', 'Active', array(new Value('Active')));
        $expectedValues[0]['date']   = new FilterStruct('datung', '29.10.2010,30.10.2010', array(new Value('29.10.2010'), new Value('30.10.2010')));

        $expectedValues[1]['user']   = new FilterStruct('user', '2,3', array(new Value('2'), new Value('3')));
        $expectedValues[1]['status'] = new FilterStruct('status', 'Active', array(new Value('Active')));
        $expectedValues[1]['date']   = new FilterStruct('datung', '29.10.2011', array(new Value('29.10.2011')));

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
        $expectedValues['user']   = new FilterStruct('user', '2,3,10-20', array(new Value('2'), new Value('3')), array(), array(2 => new Range('10', '20')));
        $expectedValues['status'] = new FilterStruct('status', 'Active', array(new Value('Active')));
        $expectedValues['date']   = new FilterStruct('date', '29-10-2010', array(new Value('2010-10-29', '29-10-2010')));
        $expectedValues['period'] = new FilterStruct('period', '>20,10', array(1 => new Value('10')), array(), array(), array(0 => new Compare('20', '>')));

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
        $expectedValues['user']    = new FilterStruct('user', '2,3,10-20', array(new Value('2'), new Value('3')), array(), array(2 => new Range('10', '20')));
        $expectedValues['invoice'] = new FilterStruct('invoice', 'F2010-48932,F2011-48932-F2012-48932', array(new Value('F2010-48932')), array(), array(1 => new Range('F2011-48932', 'F2012-48932')));
        $expectedValues['date']    = new FilterStruct('date', '29-10.2010', array(new Value('2010-10-29', '29-10.2010')));
        $expectedValues['period']  = new FilterStruct('period', '>20,10', array(1 => new Value('10')), array(), array(), array(0 => new Compare('20', '>')));

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
        $expectedValues['user']   = new FilterStruct('user', '2,3,10-20', array(new Value('2'), new Value('3')), array(), array(2 => new Range('10', '20')));
        $expectedValues['status'] = new FilterStruct('status', 'Active', array(new Value('Active')));
        $expectedValues['date']   = new FilterStruct('date', '29-10-2010', array(new Value('2010-10-29', '29-10-2010')));
        $expectedValues['period'] = new FilterStruct('period', '>20,10', array(1 => new Value('10')), array(), array(), array(0 => new Compare('20', '>')));

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