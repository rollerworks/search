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

use Rollerworks\RecordFilterBundle\Value\FilterValuesBag;
use Rollerworks\RecordFilterBundle\Type\Number;
use Rollerworks\RecordFilterBundle\Input\FilterQuery as QueryInput;
use Rollerworks\RecordFilterBundle\Value\Compare;
use Rollerworks\RecordFilterBundle\Value\Range;
use Rollerworks\RecordFilterBundle\Value\SingleValue;
use Rollerworks\RecordFilterBundle\FilterConfig;

use Rollerworks\RecordFilterBundle\Tests\Fixtures\StatusType;

class OptimizeTest extends ModifierTestCase
{
    public function testOptimizeValue()
    {
        $input = new QueryInput($this->translator);
        $input->setInput('User=2,4,10-20; Status=Active,"Not-active",Removed; date=29.10.2010; period=>20,10');

        $formatter = $this->newFormatter();
        $input->setField('user', FilterConfig::create('user', null, false, true));
        //$input->setField('status', FilterConfig::create('status'));
        $input->setField('date', FilterConfig::create('date'));
        $input->setField('period', FilterConfig::create('period', null, false, true, true));

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']   = new FilterValuesBag('user', '2,4,10-20', array(new SingleValue('2'), new SingleValue('4')), array(), array(2 => new Range('10', '20')), array(), array(), 2);
        $expectedValues['date']   = new FilterValuesBag('date', '29.10.2010', array(new SingleValue('29.10.2010')), array(), array(), array(), array(), 0);
        $expectedValues['period'] = new FilterValuesBag('period', '>20,10', array(1 => new SingleValue('10')), array(), array(), array(0 => new Compare('20', '>')), array(), 1);

        $this->assertEquals($expectedValues, $filters[0]);
    }

    public function testOptimizeValueNoOptimize()
    {
        $input = new QueryInput($this->translator);
        $input->setInput('User=2,3,10-20; Status=Active,"Not-active",Removed; date=29.10.2010; period=>20,10');

        $formatter = $this->newFormatter();
        $input->setField('user', FilterConfig::create('user', null, false, true));
        //$input->setField('status', FilterConfig::create('status'));
        $input->setField('date', FilterConfig::create('date'));
        $input->setField('period', FilterConfig::create('period', null, false, false, true));

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']   = new FilterValuesBag('user', '2,3,10-20', array(new SingleValue('2'), new SingleValue('3')), array(), array(2 => new Range('10', '20')), array(), array(), 2);
        $expectedValues['date']   = new FilterValuesBag('date', '29.10.2010', array(new SingleValue('29.10.2010')), array(), array(), array(), array(), 0);
        $expectedValues['period'] = new FilterValuesBag('period', '>20,10', array(1 => new SingleValue('10')), array(), array(), array(0 => new Compare('20', '>')), array(), 1);

        $this->assertEquals($expectedValues, $filters[0]);
    }
}
