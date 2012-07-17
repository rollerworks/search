<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Tests\Dumper;

use Rollerworks\Bundle\RecordFilterBundle\Formatter\ModifierFormatter as Formatter;
use Rollerworks\Bundle\RecordFilterBundle\Input\FilterQuery;
use Rollerworks\Bundle\RecordFilterBundle\Dumper\PHPArray;
use Rollerworks\Bundle\RecordFilterBundle\Tests\TestCase;
use Rollerworks\Bundle\RecordFilterBundle\FilterField;

class ArrayTest extends TestCase
{
    public function testOneGroupOneField()
    {
        $formatter = new Formatter($this->translator);

        $input = new FilterQuery($this->translator);
        $input->setField('user', FilterField::create('user'));
        $input->setInput('user=1;');

        $this->assertTrue($formatter->formatInput($input));

        $dumper = new PHPArray();
        $this->assertEquals(array(array('user' => array('1'))), $dumper->dumpFilters($formatter));
    }

    public function testTwoGroupsOneField()
    {
        $formatter = new Formatter($this->translator);

        $input = new FilterQuery($this->translator);
        $input->setField('user', FilterField::create('user'));
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
        $input->setField('user', FilterField::create('user'));
        $input->setField('invoice', FilterField::create('invoice'));
        $input->setInput('user=1; invoice="F2012-800";');

        $this->assertTrue($formatter->formatInput($input));

        $dumper = new PHPArray();
        $this->assertEquals(array(array('user' => array('1'), 'invoice' => array('F2012-800'))), $dumper->dumpFilters($formatter));
    }

    public function testTwoGroupsTwoFields()
    {
        $formatter = new Formatter($this->translator);

        $input = new FilterQuery($this->translator);
        $input->setField('user', FilterField::create('user'));
        $input->setField('invoice', FilterField::create('invoice'));
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
        $input->setField('user', FilterField::create('user'));
        $input->setField('invoice', FilterField::create(null, null, false, true));
        $input->setInput('(user=1; invoice="F2010-4242"-"F2012-4242";),(user=2; invoice="F2012-4242";)');

        $this->assertTrue($formatter->formatInput($input));

        $dumper = new PHPArray();
        $this->assertEquals(array(
            array('user' => array('1'), 'invoice' => array('"F2010-4242"-"F2012-4242"')),
            array('user' => array('2'), 'invoice' => array('F2012-4242'))
        ), $dumper->dumpFilters($formatter));
    }
}
