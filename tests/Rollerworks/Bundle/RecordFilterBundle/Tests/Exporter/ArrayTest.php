<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Tests\Exporter;

use Rollerworks\Bundle\RecordFilterBundle\Formatter\ModifierFormatter as Formatter;
use Rollerworks\Bundle\RecordFilterBundle\Input\FilterQuery;
use Rollerworks\Bundle\RecordFilterBundle\Exporter\ArrayExporter;
use Rollerworks\Bundle\RecordFilterBundle\Tests\TestCase;
use Rollerworks\Bundle\RecordFilterBundle\FilterField;

class ArrayTest extends TestCase
{
    public function testOneGroupOneField()
    {
        $input = new FilterQuery($this->translator);
        $input->setInput('(user=1;)');
        $input->setField('user', FilterField::create('user'));

        $formatter = new Formatter($this->translator);
        $this->assertTrue($formatter->formatInput($input));

        $dumper = new ArrayExporter();
        $this->assertEquals(
            array(array('user' => array('single-values' => array('1')))
        ), $dumper->dumpFilters($formatter));
    }

    public function testTwoGroupsOneField()
    {
        $input = new FilterQuery($this->translator);
        $input->setInput('(user=1;),(user=2;)');
        $input->setField('user', FilterField::create('user'));

        $formatter = new Formatter($this->translator);
        $this->assertTrue($formatter->formatInput($input));

        $dumper = new ArrayExporter();
        $this->assertEquals(array(
            array('user' => array('single-values' => array('1'))),
            array('user' => array('single-values' => array('2')))
        ), $dumper->dumpFilters($formatter));
    }

    public function testOneGroupTwoFields()
    {
        $input = new FilterQuery($this->translator);
        $input->setInput('user=1; invoice="F2012-800"');
        $input->setField('user', FilterField::create('user'));
        $input->setField('invoice', FilterField::create('invoice'));

        $formatter = new Formatter($this->translator);
        $this->assertTrue($formatter->formatInput($input));

        $dumper = new ArrayExporter();
        $this->assertEquals(array(
            array('user' => array('single-values' => array('1')), 'invoice' => array('single-values' => array('F2012-800')))
        ), $dumper->dumpFilters($formatter));
    }

    public function testTwoGroupsTwoFields()
    {
        $input = new FilterQuery($this->translator);
        $input->setInput('(user=1; invoice="F2010-4242";),(user=2; invoice="F2012-4242";)');
        $input->setField('user', FilterField::create('user'));
        $input->setField('invoice', FilterField::create('invoice'));

        $formatter = new Formatter($this->translator);
        $this->assertTrue($formatter->formatInput($input));

        $dumper = new ArrayExporter();
        $this->assertEquals(array(
            array('user' => array('single-values' => array('1')), 'invoice' => array('single-values' => array('F2010-4242'))),
            array('user' => array('single-values' => array('2')), 'invoice' => array('single-values' => array('F2012-4242')))
        ), $dumper->dumpFilters($formatter));
    }

    public function testRangeValue()
    {
        $input = new FilterQuery($this->translator);
        $input->setInput('(user=1; invoice="F2010-4242"-"F2012-4242";),(user=2; invoice="F2012-4242";)');
        $input->setField('user', FilterField::create('user'));
        $input->setField('invoice', FilterField::create(null, null, false, true));

        $formatter = new Formatter($this->translator);
        $this->assertTrue($formatter->formatInput($input));

        $dumper = new ArrayExporter();
        $this->assertEquals(array(
            array('user' => array('single-values' => array('1')), 'invoice' => array('ranges' => array('lower' => 'F2010-4242', 'higher' => 'F2012-4242'))),
            array('user' => array('single-values' => array('2')), 'invoice' => array('single-values' => array('F2012-4242')))
        ), $dumper->dumpFilters($formatter));
    }

    public function testExcludedValue()
    {
        $input = new FilterQuery($this->translator);
        $input->setInput('(user=!1; invoice=!"F2010-4242"-"F2012-4242";),(user=2; invoice="F2012-4242";)');
        $input->setField('user', FilterField::create('user'));
        $input->setField('invoice', FilterField::create(null, null, false, true));

        $formatter = new Formatter($this->translator);
        $this->assertTrue($formatter->formatInput($input));

        $dumper = new ArrayExporter();
        $this->assertEquals(array(
            array('user' => array('excluded-values' => array('1')), 'invoice' => array('excluded-ranges' => array('lower' => 'F2010-4242', 'higher' => 'F2012-4242'))),
            array('user' => array('single-values' => array('2')), 'invoice' => array('single-values' => array('F2012-4242')))
        ), $dumper->dumpFilters($formatter));
    }

    public function testCompareValue()
    {
        $input = new FilterQuery($this->translator);
        $input->setInput('(user=>1,<>2,>=5,<8,<=9;)');
        $input->setField('user', FilterField::create(null, null, false, false, true));
        $input->setField('invoice', FilterField::create('invoice'));

        $formatter = new Formatter($this->translator);
        $this->assertTrue($formatter->formatInput($input));

        $dumper = new ArrayExporter();
        $this->assertEquals(array(
            array('user' => array('compares' => array(
                array('operator' => '>',  'value' => '1'),
                array('operator' => '<>', 'value' => '2'),
                array('operator' => '>=', 'value' => '5'),
                array('operator' => '<',  'value' => '8'),
                array('operator' => '<=', 'value' => '9'))))
        ), $dumper->dumpFilters($formatter));
    }
}
