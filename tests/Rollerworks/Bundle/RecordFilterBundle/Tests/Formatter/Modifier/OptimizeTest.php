<?php

/*
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Tests\Formatter\Modifier;

use Rollerworks\Bundle\RecordFilterBundle\Value\FilterValuesBag;
use Rollerworks\Bundle\RecordFilterBundle\Input\FilterQuery as QueryInput;
use Rollerworks\Bundle\RecordFilterBundle\Value\Compare;
use Rollerworks\Bundle\RecordFilterBundle\Value\Range;
use Rollerworks\Bundle\RecordFilterBundle\Value\SingleValue;
use Rollerworks\Bundle\RecordFilterBundle\FilterField;
use Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\StatusType;

class OptimizeTest extends ModifierTestCase
{
    public function testOptimizeValue()
    {
        $input = new QueryInput($this->translator);
        $input->setInput('User=2,4,10-20; Status=Active,"Not-active",Removed; date=29.10.2010; period=>20,10');

        $formatter = $this->newFormatter();
        $input->setField('user', FilterField::create('user', null, false, true));
        $input->setField('status', FilterField::create('status', new StatusType()));
        $input->setField('date', FilterField::create('date'));
        $input->setField('period', FilterField::create('period', null, false, true, true));

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']   = new FilterValuesBag('user', '2,4,10-20', array(new SingleValue('2'), new SingleValue('4')), array(), array(2 => new Range('10', '20')));
        $expectedValues['date']   = new FilterValuesBag('date', '29.10.2010', array(new SingleValue('29.10.2010')));
        $expectedValues['period'] = new FilterValuesBag('period', '>20,10', array(1 => new SingleValue('10')), array(), array(), array(0 => new Compare('20', '>')));

        $this->assertEquals($expectedValues, $filters[0]);
    }

    public function testOptimizeValueNoOptimize()
    {
        $input = new QueryInput($this->translator);
        $input->setInput('User=2,3,10-20; Status=Active,"Not-active"; date=29.10.2010; period=>20,10');

        $formatter = $this->newFormatter();
        $input->setField('user', FilterField::create('user', null, false, true));
        $input->setField('status', FilterField::create('status', new StatusType()));
        $input->setField('date', FilterField::create('date'));
        $input->setField('period', FilterField::create('period', null, false, false, true));

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $filters = $formatter->getFilters();

        $expectedValues = array();
        $expectedValues['user']   = new FilterValuesBag('user', '2,3,10-20', array(new SingleValue('2'), new SingleValue('3')), array(), array(2 => new Range('10', '20')));
        $expectedValues['status'] = new FilterValuesBag('status', 'Active,"Not-active"', array(new SingleValue('1', 'Active'), new SingleValue('0', 'Not-active')));
        $expectedValues['date']   = new FilterValuesBag('date', '29.10.2010', array(new SingleValue('29.10.2010')));
        $expectedValues['period'] = new FilterValuesBag('period', '>20,10', array(1 => new SingleValue('10')), array(), array(), array(0 => new Compare('20', '>')));

        $this->assertEquals($expectedValues, $filters[0]);
    }
}
