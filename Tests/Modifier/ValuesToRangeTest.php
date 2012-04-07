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
use Rollerworks\RecordFilterBundle\Formatter\Modifier\ValueOptimizer;
use Rollerworks\RecordFilterBundle\Type\Date;
use Rollerworks\RecordFilterBundle\Type\DateTime;
use Rollerworks\RecordFilterBundle\Type\Decimal;
use Rollerworks\RecordFilterBundle\Type\Number;
use Rollerworks\RecordFilterBundle\Input\FilterQuery as QueryInput;
use Rollerworks\RecordFilterBundle\Value\Compare;
use Rollerworks\RecordFilterBundle\Value\Range;
use Rollerworks\RecordFilterBundle\Value\SingleValue;

use Rollerworks\RecordFilterBundle\Tests\Fixtures\StatusType;

class ValuesToRangeTest extends TestCase
{
    function testOptimizeValue()
    {
        $input = new QueryInput();
        $input->setQueryString('User=1,2,3,4,5,6,7');

        $formatter = $this->newFormatter();
        $input->setField('user', 'user', new Number(), false, true);

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user'] = new FilterValuesBag('user', '1,2,3,4,5,6,7', array(), array(), array(7 => new Range('1', '7')), array(), array(), 7);

        $this->assertEquals($expectedValues, $filters[0]);
    }

    function testOptimizeValueUnordered()
    {
        $input = new QueryInput();
        $input->setQueryString('User=3,6,7,1,2,4,5');

        $formatter = $this->newFormatter();
        $input->setField('user', 'user', new Number(), false, true);

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user'] = new FilterValuesBag('user', '3,6,7,1,2,4,5', array(), array(), array(7 => new Range('1', '7')), array(), array(), 7);

        $this->assertEquals($expectedValues, $filters[0]);
    }

    function testOptimizeValueMultipleRanges()
    {
        $input = new QueryInput();
        $input->setQueryString('User=1,2,3,4,5,6,7,10,11,12,13,14,15,18');

        $formatter = $this->newFormatter();
        $input->setField('user', 'user', new Number(), false, true);

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user'] = new FilterValuesBag('user', '1,2,3,4,5,6,7,10,11,12,13,14,15,18', array(13 => new SingleValue('18')), array(), array(14 => new Range('1', '7'), 15 => new Range('10', '15')), array(), array(), 15);

        $this->assertEquals($expectedValues, $filters[0]);
    }


    function testOptimizeExcludes()
    {
        $input = new QueryInput();
        $input->setQueryString('User=!1,!2,!3,!4,!5,!6,!7');

        $formatter = $this->newFormatter();
        $input->setField('user', 'user', new Number(), false, true);

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user'] = new FilterValuesBag('user', '!1,!2,!3,!4,!5,!6,!7', array(), array(), array(), array(), array(7 => new Range('1', '7')), 7);

        $this->assertEquals($expectedValues, $filters[0]);
    }

    function testOptimizeExcludesUnordered()
    {
        $input = new QueryInput();
        $input->setQueryString('User=!3,!6,!7,!1,!2,!4,!5');

        $formatter = $this->newFormatter();
        $input->setField('user', 'user', new Number(), false, true);

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user'] = new FilterValuesBag('user', '!3,!6,!7,!1,!2,!4,!5', array(), array(), array(), array(), array(7 => new Range('1', '7')), 7);

        $this->assertEquals($expectedValues, $filters[0]);
    }

    function testOptimizeExcludesMultipleRanges()
    {
        $input = new QueryInput();
        $input->setQueryString('User=!1,!2,!3,!4,!5,!6,!7,!10,!11,!12,!13,!14,!15,!18');

        $formatter = $this->newFormatter();
        $input->setField('user', 'user', new Number(), false, true);

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user'] = new FilterValuesBag('user', '!1,!2,!3,!4,!5,!6,!7,!10,!11,!12,!13,!14,!15,!18', array(), array(13 => new SingleValue('18')), array(), array(), array(14 => new Range('1', '7'), 15 => new Range('10', '15')), 15);

        $this->assertEquals($expectedValues, $filters[0]);
    }
}