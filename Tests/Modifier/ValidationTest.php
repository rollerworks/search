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

use Rollerworks\RecordFilterBundle\FilterStruct;
use Rollerworks\RecordFilterBundle\Formatter\Type\Date;
use Rollerworks\RecordFilterBundle\Formatter\Type\DateTime;
use Rollerworks\RecordFilterBundle\Formatter\Type\Decimal;
use Rollerworks\RecordFilterBundle\Formatter\Type\Number;
use Rollerworks\RecordFilterBundle\Input\Query as QueryInput;
use Rollerworks\RecordFilterBundle\Struct\Compare;
use Rollerworks\RecordFilterBundle\Struct\Range;
use Rollerworks\RecordFilterBundle\Struct\Value;

class ValidationTest extends TestCase
{
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
}