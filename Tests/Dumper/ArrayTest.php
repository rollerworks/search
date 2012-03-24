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

namespace Rollerworks\RecordFilterBundle\Tests\Dumper;

use Rollerworks\RecordFilterBundle\Formatter\Formatter;
use Rollerworks\RecordFilterBundle\Input\Query;
use Rollerworks\RecordFilterBundle\Dumper\PHPArray;

class ArrayTest extends \Rollerworks\RecordFilterBundle\Tests\Factory\FactoryTestCase
{
    function testOneGroupOneField()
    {
        $formatter = new Formatter($this->translator);
        $formatter->setField('user');

        $this->assertTrue($formatter->formatInput(new Query('user=1;')));

        $dumper = new PHPArray();
        $this->assertEquals(array(array('user' => array('1'))), $dumper->dumpFilters($formatter));
    }

    function testTwoGroupsOneField()
    {
        $formatter = new Formatter($this->translator);
        $formatter->setField('user');

        $this->assertTrue($formatter->formatInput(new Query('(user=1;),(user=2;)')));

        $dumper = new PHPArray();
        $this->assertEquals(array(
            array('user' => array('1')),
            array('user' => array('2'))
        ), $dumper->dumpFilters($formatter));
    }


    function testOneGroupTwoFields()
    {
        $formatter = new Formatter($this->translator);
        $formatter->setField('user');
        $formatter->setField('invoice');

        $this->assertTrue($formatter->formatInput(new Query('user=1; invoice="F2012-800";')));

        $dumper = new PHPArray();
        $this->assertEquals(array(array('user' => array('1'), 'invoice' => array('F2012-800'))), $dumper->dumpFilters($formatter));
    }

    function testTwoGroupsTwoFields()
    {
        $formatter = new Formatter($this->translator);
        $formatter->setField('user');
        $formatter->setField('invoice');

        $this->assertTrue($formatter->formatInput(new Query('(user=1; invoice="F2010-4242";),(user=2; invoice="F2012-4242";)')));

        $dumper = new PHPArray();
        $this->assertEquals(array(
            array('user' => array('1'), 'invoice' => array('F2010-4242')),
            array('user' => array('2'), 'invoice' => array('F2012-4242'))
        ), $dumper->dumpFilters($formatter));
    }

    function testRangeValue()
    {
        $formatter = new Formatter($this->translator);
        $formatter->setField('user');
        $formatter->setField('invoice', null, false, true);

        $this->assertTrue($formatter->formatInput(new Query('(user=1; invoice="F2010-4242"-"F2012-4242";),(user=2; invoice="F2012-4242";)')));

        $dumper = new PHPArray();
        $this->assertEquals(array(
            array('user' => array('1'), 'invoice' => array('"F2010-4242"-"F2012-4242"')),
            array('user' => array('2'), 'invoice' => array('F2012-4242'))
        ), $dumper->dumpFilters($formatter));
    }
}
