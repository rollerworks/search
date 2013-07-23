<?php

/*
 * This file is part of the RollerworksRecordFilterBundle package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Tests\Exporter;

use Rollerworks\Bundle\RecordFilterBundle\Exporter\FilterQueryExporter;
use Rollerworks\Bundle\RecordFilterBundle\Formatter\ModifierFormatter as Formatter;
use Rollerworks\Bundle\RecordFilterBundle\Input\FilterQuery as QueryInput;
use Rollerworks\Bundle\RecordFilterBundle\Tests\TestCase;
use Rollerworks\Bundle\RecordFilterBundle\FilterField;

class FilterQueryTest extends TestCase
{
    public function testSimple()
    {
        $input = new QueryInput($this->translator);
        $input->setInput('User=2,3,10-20; Status=Active; date=29.10.2010; period=>20,10');

        $formatter = new Formatter($this->translator);
        $input->setField('user', FilterField::create(null, null, false, true));
        $input->setField('status', FilterField::create('status'));
        $input->setField('date', FilterField::create('date'));
        $input->setField('period', FilterField::create(null, null, false, false, true));

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $dumper = new FilterQueryExporter();

        $this->assertEquals('( user="2", "3", "10"-"20"; status="Active"; date="29.10.2010"; period="10", >20; )', $dumper->dumpFilters($formatter));
        $this->assertEquals('( user="2", "3", "10"-"20";'.PHP_EOL.'status="Active";'.PHP_EOL.'date="29.10.2010";'.PHP_EOL.'period="10", >20; )', $dumper->dumpFilters($formatter, true));
    }

    public function testQuoted()
    {
        $input = new QueryInput($this->translator);
        $input->setInput('User="2""",3,"10"""-20; Status=Active; date=29.10.2010; period=>20,10');

        $formatter = new Formatter($this->translator);
        $input->setField('user', FilterField::create(null, null, false, true));
        $input->setField('status', FilterField::create('status'));
        $input->setField('date', FilterField::create('date'));
        $input->setField('period', FilterField::create(null, null, false, false, true));

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $dumper = new FilterQueryExporter();

        $this->assertEquals('( user="2""", "3", "10"""-"20"; status="Active"; date="29.10.2010"; period="10", >20; )', $dumper->dumpFilters($formatter));
        $this->assertEquals('( user="2""", "3", "10"""-"20";'.PHP_EOL.'status="Active";'.PHP_EOL.'date="29.10.2010";'.PHP_EOL.'period="10", >20; )', $dumper->dumpFilters($formatter, true));
    }

    public function testWithGroups()
    {
        $input = new QueryInput($this->translator);
        $input->setInput('(User=2,3,10-20,!30-50; Status=Active; date=29.10.2010; period=>20,10;), (User=5,9; Status="None-active"; date=29.10.2012;)');

        $formatter = new Formatter($this->translator);
        $input->setField('user', FilterField::create(null, null, false, true));
        $input->setField('status', FilterField::create('status'));
        $input->setField('date', FilterField::create('date'));
        $input->setField('period', FilterField::create(null, null, false, false, true));

        if (!$formatter->formatInput($input)) {
            $this->fail(print_r($formatter->getMessages(), true));
        }

        $dumper = new FilterQueryExporter();

        $this->assertEquals('( user="2", "3", "10"-"20", !"30"-"50"; status="Active"; date="29.10.2010"; period="10", >20; ), ( user="5", "9"; status="None-active"; date="29.10.2012"; )', $dumper->dumpFilters($formatter));
        $this->assertEquals('( user="2", "3", "10"-"20", !"30"-"50";'.PHP_EOL.'status="Active";'.PHP_EOL.'date="29.10.2010";'.PHP_EOL.'period="10", >20; ), ( user="5", "9";'.PHP_EOL.'status="None-active";'.PHP_EOL.'date="29.10.2012"; )', $dumper->dumpFilters($formatter, true));
    }
}
