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
use Rollerworks\RecordFilterBundle\Formatter\Modifier\ValueOptimizer;
use Rollerworks\RecordFilterBundle\Formatter\Type\Date;
use Rollerworks\RecordFilterBundle\Formatter\Type\DateTime;
use Rollerworks\RecordFilterBundle\Formatter\Type\Decimal;
use Rollerworks\RecordFilterBundle\Formatter\Type\Number;
use Rollerworks\RecordFilterBundle\Input\Query as QueryInput;
use Rollerworks\RecordFilterBundle\Struct\Compare;
use Rollerworks\RecordFilterBundle\Struct\Range;
use Rollerworks\RecordFilterBundle\Struct\Value;

use Rollerworks\RecordFilterBundle\Tests\Fixtures\StatusType;

class OptimizeTest extends TestCase
{
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
}