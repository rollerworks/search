<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\RecordFilterBundle\Tests\Dumper;

use Rollerworks\RecordFilterBundle\Dumper\FilterQuery;
use Rollerworks\RecordFilterBundle\Formatter\Formatter;
use Rollerworks\RecordFilterBundle\Input\FilterQuery as QueryInput;

class FilterQueryTest extends \Rollerworks\RecordFilterBundle\Tests\TestCase
{
    public function testSimple()
    {
        $input = new QueryInput();
        $input->setInput('User=2,3,10-20; Status=Active; date=29.10.2010; period=>20,10');

        $formatter = new Formatter($this->translator);
        $input->setField('user', null, null, false, true);
        $input->setField('status');
        $input->setField('date');
        $input->setField('period', null, null, false, false, true);

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $dumper = new FilterQuery();

        $this->assertEquals('( user="2", "3", "10"-"20"; status="Active"; date="29.10.2010"; period="10", >20; )', $dumper->dumpFilters($formatter));
        $this->assertEquals('( user="2", "3", "10"-"20";'.PHP_EOL.'status="Active";'.PHP_EOL.'date="29.10.2010";'.PHP_EOL.'period="10", >20; )', $dumper->dumpFilters($formatter, true));
    }

    public function testQuoted()
    {
        $input = new QueryInput();
        $input->setInput('User="2""",3,"10"""-20; Status=Active; date=29.10.2010; period=>20,10');

        $formatter = new Formatter($this->translator);
        $input->setField('user', null, null, false, true);
        $input->setField('status');
        $input->setField('date');
        $input->setField('period', null, null, false, false, true);

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $dumper = new FilterQuery();

        $this->assertEquals('( user="2""", "3", "10"""-"20"; status="Active"; date="29.10.2010"; period="10", >20; )', $dumper->dumpFilters($formatter));
        $this->assertEquals('( user="2""", "3", "10"""-"20";'.PHP_EOL.'status="Active";'.PHP_EOL.'date="29.10.2010";'.PHP_EOL.'period="10", >20; )', $dumper->dumpFilters($formatter, true));
    }

    public function testWithGroups()
    {
        $input = new QueryInput();
        $input->setInput('(User=2,3,10-20,!30-50; Status=Active; date=29.10.2010; period=>20,10;), (User=5,9; Status="None-active"; date=29.10.2012;)');

        $formatter = new Formatter($this->translator);
        $input->setField('user', null, null, false, true);
        $input->setField('status');
        $input->setField('date');
        $input->setField('period', null, null, false, false, true);

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $dumper = new FilterQuery();

        $this->assertEquals('( user="2", "3", "10"-"20", !"30"-"50"; status="Active"; date="29.10.2010"; period="10", >20; ), ( user="5", "9"; status="None-active"; date="29.10.2012"; )', $dumper->dumpFilters($formatter));
        $this->assertEquals('( user="2", "3", "10"-"20", !"30"-"50";'.PHP_EOL.'status="Active";'.PHP_EOL.'date="29.10.2010";'.PHP_EOL.'period="10", >20; ), ( user="5", "9";'.PHP_EOL.'status="None-active";'.PHP_EOL.'date="29.10.2012"; )', $dumper->dumpFilters($formatter, true));
    }
}
