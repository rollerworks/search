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

use Rollerworks\RecordFilterBundle\Formatter\Formatter;
use Rollerworks\RecordFilterBundle\Input\FilterQuery;
use Rollerworks\RecordFilterBundle\Dumper\PHPArray;
use Rollerworks\RecordFilterBundle\Tests\TestCase;
use Rollerworks\RecordFilterBundle\FilterConfig;

class ArrayTest extends TestCase
{
    public function testOneGroupOneField()
    {
        $formatter = new Formatter($this->translator);

        $input = new FilterQuery($this->translator);
        $input->setField('user', FilterConfig::create('user'));
        $input->setInput('user=1;');

        $this->assertTrue($formatter->formatInput($input));

        $dumper = new PHPArray();
        $this->assertEquals(array(array('user' => array('1'))), $dumper->dumpFilters($formatter));
    }

    public function testTwoGroupsOneField()
    {
        $formatter = new Formatter($this->translator);

        $input = new FilterQuery($this->translator);
        $input->setField('user', FilterConfig::create('user'));
        $input->setInput('(user=1;),(user=2;)');

        $this->assertTrue($formatter->formatInput($input));

        $dumper = new PHPArray();
        $this->assertEquals(array(
            array('user' => array('1')),
            array('user' => array('2'))
        ), $dumper->dumpFilters($formatter));
    }

    public function testOneGroupTwoFields()
    {
        $formatter = new Formatter($this->translator);

        $input = new FilterQuery($this->translator);
        $input->setField('user', FilterConfig::create('user'));
        $input->setField('invoice', FilterConfig::create('invoice'));
        $input->setInput('user=1; invoice="F2012-800";');

        $this->assertTrue($formatter->formatInput($input));

        $dumper = new PHPArray();
        $this->assertEquals(array(array('user' => array('1'), 'invoice' => array('F2012-800'))), $dumper->dumpFilters($formatter));
    }

    public function testTwoGroupsTwoFields()
    {
        $formatter = new Formatter($this->translator);

        $input = new FilterQuery($this->translator);
        $input->setField('user', FilterConfig::create('user'));
        $input->setField('invoice', FilterConfig::create('invoice'));
        $input->setInput('(user=1; invoice="F2010-4242";),(user=2; invoice="F2012-4242";)');

        $this->assertTrue($formatter->formatInput($input));

        $dumper = new PHPArray();
        $this->assertEquals(array(
            array('user' => array('1'), 'invoice' => array('F2010-4242')),
            array('user' => array('2'), 'invoice' => array('F2012-4242'))
        ), $dumper->dumpFilters($formatter));
    }

    public function testRangeValue()
    {
        $formatter = new Formatter($this->translator);

        $input = new FilterQuery($this->translator);
        $input->setField('user', FilterConfig::create('user'));
        $input->setField('invoice', FilterConfig::create(null, null, false, true));
        $input->setInput('(user=1; invoice="F2010-4242"-"F2012-4242";),(user=2; invoice="F2012-4242";)');

        $this->assertTrue($formatter->formatInput($input));

        $dumper = new PHPArray();
        $this->assertEquals(array(
            array('user' => array('1'), 'invoice' => array('"F2010-4242"-"F2012-4242"')),
            array('user' => array('2'), 'invoice' => array('F2012-4242'))
        ), $dumper->dumpFilters($formatter));
    }
}
