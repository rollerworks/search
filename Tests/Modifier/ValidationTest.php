<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Tests\Modifier;

use Rollerworks\Bundle\RecordFilterBundle\Type\Date;
use Rollerworks\Bundle\RecordFilterBundle\Input\FilterQuery as QueryInput;
use Rollerworks\Bundle\RecordFilterBundle\Value\Range;
use Rollerworks\Bundle\RecordFilterBundle\FilterConfig;

class ValidationTest extends ModifierTestCase
{
    public function testValidationReq()
    {
        $input = new QueryInput($this->translator);
        $input->setField('period', FilterConfig::create('period', new Date(), true));
        $input->setField('User', FilterConfig::create('User', null, true));

        $input->setInput('User=2; Status=Active; date=29.10.2010');

        $this->assertFalse($input->getGroups());
        $this->assertEquals(array("Field 'period' is required in group 1."), $input->getMessages());
    }

    public function testValidationReqEmptyField()
    {
        $input = new QueryInput($this->translator);
        $input->setInput('User=2; Status=Active; date=29.10.2010; period=,;');
        $input->setField('period', FilterConfig::create('period', new Date(), true));
        $input->setField('User', FilterConfig::create('User', null, true));

        $this->assertFalse($input->getGroups());
        $this->assertEquals(array("Field 'period' is required in group 1."), $input->getMessages());
    }

    public function testValidationEmptyField()
    {
        $input = new QueryInput($this->translator);
        $input->setInput('User=2; Status=Active; date=29.10.2010; period=,;');

        $formatter = $this->newFormatter();

        $input->setField('period', FilterConfig::create('period'));
        $input->setField('User', FilterConfig::create('User'));

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }
    }

    public function testValidationFail()
    {
        $input = new QueryInput($this->translator);
        $input->setInput('User=2; Status=Active; period=2910.2010');

        $formatter = $this->newFormatter();

        $input->setField('period', FilterConfig::create('period', new Date(), true));
        $input->setField('User', FilterConfig::create('User', null, true));

        $input->setField('period', FilterConfig::create('period', new Date(), false, true));
        $input->setField('User', FilterConfig::create('User', null, false, true));

        $this->assertFalse($formatter->formatInput($input));

        $messages = $formatter->getMessages();
        $this->assertEquals(array('Validation error(s) in field \'period\' at value "2910.2010" in group 1: This value is not a valid date.'), $messages['error']);
    }

    public function testValidationFailInGroup()
    {
        $input = new QueryInput($this->translator);
        $input->setInput('(User=2; Status=Active; period=2910.2010;),(User=2; Status=Active; period=2910.2010;)');

        $formatter = $this->newFormatter();
        $input->setField('period', FilterConfig::create('period', new Date(), false, true));
        $input->setField('User', FilterConfig::create('User', null, false, true));

        $this->assertFalse($formatter->formatInput($input));

        $messages = $formatter->getMessages();
        $this->assertEquals(array(
            'Validation error(s) in field \'period\' at value "2910.2010" in group 1: This value is not a valid date.',
            'Validation error(s) in field \'period\' at value "2910.2010" in group 2: This value is not a valid date.'
        ), $messages['error']);
    }

    public function testValidationFailInGroupNoResult()
    {
        $input = new QueryInput($this->translator);
        $input->setInput('(User=2; Status=Active; period=2910.2010;),(User=2; Status=Active; period=29.10.2010;)');

        $formatter = $this->newFormatter();
        $input->setField('period', FilterConfig::create('period', new Date(), false, true));
        $input->setField('User', FilterConfig::create('User', null, false, true));

        $this->assertFalse($formatter->formatInput($input));

        $messages = $formatter->getMessages();
        $this->assertEquals(array('Validation error(s) in field \'period\' at value "2910.2010" in group 1: This value is not a valid date.'), $messages['error']);

        $this->setExpectedException('\RuntimeException', 'formatInput() must be executed before calling this function.');
        $formatter->getFilters();
    }

    public function testValidationFaiInlRange()
    {
        $input = new QueryInput($this->translator);
        $input->setInput('User=2; Status=Active; period=25.10.2010-3110.2010');

        $formatter = $this->newFormatter();
        $input->setField('period', FilterConfig::create('period', new Date(), true, true));
        $input->setField('User', FilterConfig::create('User', null, false, true));

        $this->assertFalse($formatter->formatInput($input));

        $messages = $formatter->getMessages();
        $this->assertEquals(array('Validation error(s) in field \'period\' at value "25.10.2010"-"3110.2010" in group 1: This value is not a valid date.'), $messages['error']);
    }

    public function testValidationFaiInlRange2()
    {
        $input = new QueryInput($this->translator);
        $input->setInput('User=2; Status=Active; period=2510.2010-3110.2010');

        $formatter = $this->newFormatter();
        $input->setField('period', FilterConfig::create('period', new Date(), true, true));
        $input->setField('User', FilterConfig::create('User', null, false, true));

        $this->assertFalse($formatter->formatInput($input));

        $messages = $formatter->getMessages();
        $this->assertEquals(array('Validation error(s) in field \'period\' at value "2510.2010"-"3110.2010" in group 1: This value is not a valid date.'), $messages['error']);
    }

    // Validation:Range

    public function testValidationRangeNotLower()
    {
        $input = new QueryInput($this->translator);
        $input->setInput('User=2; Status=Active; period=31.10.2010-25.10.2010');

        $formatter = $this->newFormatter();
        $input->setField('period', FilterConfig::create('period', new Date(), true, true));
        $input->setField('User', FilterConfig::create('User', null, false, true));

        $this->assertFalse($formatter->formatInput($input));

        $messages = $formatter->getMessages();
        $this->assertEquals(array('Validation error in field \'period\': \'31.10.2010\' is not lower then \'25.10.2010\' in group 1.'), $messages['error']);
    }

    public function testValidationFaiInCompare()
    {
        $input = new QueryInput($this->translator);
        $input->setInput('User=2; Status=Active; period=<10.10.2010,>3110.2010');

        $formatter = $this->newFormatter();
        $input->setField('period', FilterConfig::create('period', new Date(), true, true, true));
        $input->setField('User', FilterConfig::create('User', null, false, true));

        $this->assertFalse($formatter->formatInput($input));

        $messages = $formatter->getMessages();
        $this->assertEquals(array('Validation error(s) in field \'period\' at value >"3110.2010" in group 1: This value is not a valid date.'), $messages['error']);
    }

    public function testValidationFaiInExclude()
    {
        $input = new QueryInput($this->translator);
        $input->setInput('User=2; Status=Active; period=10.10.2010,!3110.2010');

        $formatter = $this->newFormatter();
        $input->setField('period', FilterConfig::create('period', new Date(), true, true));
        $input->setField('User', FilterConfig::create('User', null, false, true));

        $this->assertFalse($formatter->formatInput($input));

        $messages = $formatter->getMessages();
        $this->assertEquals(array('Validation error(s) in field \'period\' at value !"3110.2010" in group 1: This value is not a valid date.'), $messages['error']);
    }

    public function testValidationExcludeInInclude()
    {
        $input = new QueryInput($this->translator);
        $input->setInput('User=2; Status=Active; period=10.10.2010,!31.10.2010,31.10.2010');

        $formatter = $this->newFormatter();
        $input->setField('period', FilterConfig::create('period', new Date(), true, true));
        $input->setField('User', FilterConfig::create('User', null, false, true));

        $this->assertFalse($formatter->formatInput($input));

        $messages = $formatter->getMessages();
        $this->assertEquals(array('Value !"31.10.2010" in field \'period\' is already marked as included and can\'t be excluded in group 1.'), $messages['error']);
    }

    public function testValidationIncludeInExclude()
    {
        $input = new QueryInput($this->translator);
        $input->setInput('User=2; Status=Active; period=10.10.2010,31.10.2010,!31.10.2010');

        $formatter = $this->newFormatter();
        $input->setField('period', FilterConfig::create('period', new Date(), true, true));
        $input->setField('User', FilterConfig::create('User', null, false, true));

        $this->assertFalse($formatter->formatInput($input));

        $messages = $formatter->getMessages();
        $this->assertEquals(array('Value !"31.10.2010" in field \'period\' is already marked as included and can\'t be excluded in group 1.'), $messages['error']);
    }

    public function testNoValidation()
    {
        $input = new QueryInput($this->translator);
        $input->setInput('User=2; Status=Active; period=29.10.2010');

        $formatter = $this->newFormatter();
        $input->setField('period', FilterConfig::create('period', new Date(), false, true));

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }
    }

    // Test to make sure there are no duplicate warning messages
    public function testValidationInlRangeNoValidation()
    {
        $input = new QueryInput($this->translator);
        $input->setInput('User=2-5,8-10; Status=Active; period=25.10.2010-31.10.2010,25.10.2011-31.10.2011');

        $formatter = $this->newFormatter();
        $input->setField('period', FilterConfig::create('period', null, true, true));
        $input->setField('User', FilterConfig::create('User', null, true, true));

        $this->assertTrue($formatter->formatInput($input));
    }
}
