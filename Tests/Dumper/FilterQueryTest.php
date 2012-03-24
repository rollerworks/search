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
use Rollerworks\RecordFilterBundle\Input\Query as QueryInput;
use Rollerworks\RecordFilterBundle\Dumper\FilterQuery;

class FilterQueryTest extends \Rollerworks\RecordFilterBundle\Tests\Factory\FactoryTestCase
{
    function testSimple()
    {
        $input = new QueryInput();
        $input->setQueryString('User=2,3,10-20; Status=Active; date=29.10.2010; period=>20,10');

        $formatter = new Formatter($this->translator);
        $formatter->setField('user', null, false, true);
        $formatter->setField('status');
        $formatter->setField('date', null);
        $formatter->setField('period', null, false, false, true);

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $dumper = new FilterQuery();

        $this->assertEquals('( user="2", "3", "10"-"20"; status="Active"; date="29.10.2010"; period="10", >20; )', $dumper->dumpFilters($formatter));
        $this->assertEquals('( user="2", "3", "10"-"20";'.PHP_EOL.'status="Active";'.PHP_EOL.'date="29.10.2010";'.PHP_EOL.'period="10", >20; )', $dumper->dumpFilters($formatter, true));
    }

    function testQuoted()
    {
        $input = new QueryInput();
        $input->setQueryString('User="2""",3,"10"""-20; Status=Active; date=29.10.2010; period=>20,10');

        $formatter = new Formatter($this->translator);
        $formatter->setField('user', null, false, true);
        $formatter->setField('status');
        $formatter->setField('date', null);
        $formatter->setField('period', null, false, false, true);

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $dumper = new FilterQuery();

        $this->assertEquals('( user="2""", "3", "10"""-"20"; status="Active"; date="29.10.2010"; period="10", >20; )', $dumper->dumpFilters($formatter));
        $this->assertEquals('( user="2""", "3", "10"""-"20";'.PHP_EOL.'status="Active";'.PHP_EOL.'date="29.10.2010";'.PHP_EOL.'period="10", >20; )', $dumper->dumpFilters($formatter, true));
    }

    function testWithGroups()
    {
        $input = new QueryInput();
        $input->setQueryString('(User=2,3,10-20,!30-50; Status=Active; date=29.10.2010; period=>20,10;), (User=5,9; Status="None-active"; date=29.10.2012;)');

        $formatter = new Formatter($this->translator);
        $formatter->setField('user', null, false, true);
        $formatter->setField('status');
        $formatter->setField('date', null);
        $formatter->setField('period', null, false, false, true);

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $dumper = new FilterQuery();

        $this->assertEquals('( user="2", "3", "10"-"20", !"30"-"50"; status="Active"; date="29.10.2010"; period="10", >20; ), ( user="5", "9"; status="None-active"; date="29.10.2012"; )', $dumper->dumpFilters($formatter));
        $this->assertEquals('( user="2", "3", "10"-"20", !"30"-"50";'.PHP_EOL.'status="Active";'.PHP_EOL.'date="29.10.2010";'.PHP_EOL.'period="10", >20; ), ( user="5", "9";'.PHP_EOL.'status="None-active";'.PHP_EOL.'date="29.10.2012"; )', $dumper->dumpFilters($formatter, true));
    }
}
