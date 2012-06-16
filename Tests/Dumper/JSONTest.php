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

use Rollerworks\Bundle\RecordFilterBundle\Formatter\Formatter;
use Rollerworks\Bundle\RecordFilterBundle\Input\FilterQuery;
use Rollerworks\Bundle\RecordFilterBundle\Dumper\JSON;
use Rollerworks\Bundle\RecordFilterBundle\Tests\TestCase;
use Rollerworks\Bundle\RecordFilterBundle\FilterConfig;

class JSONTest extends TestCase
{
    public function testFlattenedOneGroupOneField()
    {
        $input = new FilterQuery($this->translator);
        $input->setInput('user=1;');
        $input->setField('user', FilterConfig::create('user'));

        $formatter = new Formatter($this->translator);
        $this->assertTrue($formatter->formatInput($input));

        $dumper = new JSON();
        $this->assertEquals(json_encode(array(array('user' => array('1')))), $dumper->dumpFilters($formatter, true));
    }

    public function testFlattenedTwoGroupsOneField()
    {
        $input = new FilterQuery($this->translator);
        $input->setInput('(user=1;),(user=2;)');
        $input->setField('user', FilterConfig::create('user'));

        $formatter = new Formatter($this->translator);
        $this->assertTrue($formatter->formatInput($input));

        $dumper = new JSON();
        $this->assertEquals(json_encode(array(
            array('user' => array('1')),
            array('user' => array('2')))
        ), $dumper->dumpFilters($formatter, true));
    }

    public function testFlattenedFlattenedOneGroupTwoFields()
    {
        $input = new FilterQuery($this->translator);
        $input->setInput('user=1; invoice="F2012-800";');
        $input->setField('user', FilterConfig::create('user'));
        $input->setField('invoice', FilterConfig::create('invoice'));

        $formatter = new Formatter($this->translator);
        $this->assertTrue($formatter->formatInput($input));

        $dumper = new JSON();
        $this->assertEquals(json_encode(array(array('user' => array('1'), 'invoice' => array('F2012-800')))), $dumper->dumpFilters($formatter, true));
    }

    public function testFlattenedTwoGroupsTwoFields()
    {
        $input = new FilterQuery($this->translator);
        $input->setInput('(user=1; invoice="F2010-4242";),(user=2; invoice="F2012-4242";)');
        $input->setField('user', FilterConfig::create('user'));
        $input->setField('invoice', FilterConfig::create('invoice'));

        $formatter = new Formatter($this->translator);
        $this->assertTrue($formatter->formatInput($input));

        $dumper = new JSON();
        $this->assertEquals(json_encode(array(
            array('user' => array('1'), 'invoice' => array('F2010-4242')),
            array('user' => array('2'), 'invoice' => array('F2012-4242')))
        ), $dumper->dumpFilters($formatter, true));
    }

    public function testFlattenedRangeValue()
    {
        $input = new FilterQuery($this->translator);
        $input->setInput('(user=1; invoice="F2010-4242"-"F2012-4242";),(user=2; invoice="F2012-4242";)');
        $input->setField('user', FilterConfig::create('user'));
        $input->setField('invoice', FilterConfig::create(null, null, false, true));

        $formatter = new Formatter($this->translator);
        $this->assertTrue($formatter->formatInput($input));

        $dumper = new JSON();
        $this->assertEquals(json_encode(array(
            array('user' => array('1'), 'invoice' => array('"F2010-4242"-"F2012-4242"')),
            array('user' => array('2'), 'invoice' => array('F2012-4242')))
        ), $dumper->dumpFilters($formatter, true));
    }

    public function testFlattenedExcludedValue()
    {
        $input = new FilterQuery($this->translator);
        $input->setInput('(user=!1; invoice=!"F2010-4242"-"F2012-4242";),(user=2; invoice="F2012-4242";)');
        $input->setField('user', FilterConfig::create('user'));
        $input->setField('invoice', FilterConfig::create(null, null, false, true));

        $formatter = new Formatter($this->translator);
        $this->assertTrue($formatter->formatInput($input));

        $dumper = new JSON();
        $this->assertEquals(json_encode(array(
            array('user' => array('!1'), 'invoice' => array('!"F2010-4242"-"F2012-4242"')),
            array('user' => array('2'), 'invoice' => array('F2012-4242')))
        ), $dumper->dumpFilters($formatter, true));
    }

    public function testFlattenedCompareValue()
    {
        $input = new FilterQuery($this->translator);
        $input->setInput('(user=>1,<>2,>=5,<8,<=9;)');
        $input->setField('user', FilterConfig::create(null, null, false, true, true));
        $input->setField('invoice', FilterConfig::create('invoice'));

        $formatter = new Formatter($this->translator);
        $this->assertTrue($formatter->formatInput($input));

        $dumper = new JSON();
        $this->assertEquals(json_encode(array(array('user' => array('>1', '<>2', '>=5', '<8', '<=9')))), $dumper->dumpFilters($formatter, true));
    }

    public function testOneGroupOneField()
    {
        $input = new FilterQuery($this->translator);
        $input->setInput('(user=1;)');
        $input->setField('user', FilterConfig::create('user'));

        $formatter = new Formatter($this->translator);
        $this->assertTrue($formatter->formatInput($input));

        $dumper = new JSON();
        $this->assertEquals(json_encode(array(array('user' => array( 'single-values' => array('1'))))), $dumper->dumpFilters($formatter));
    }

    public function testTwoGroupsOneField()
    {
        $input = new FilterQuery($this->translator);
        $input->setInput('(user=1;),(user=2;)');
        $input->setField('user', FilterConfig::create('user'));

        $formatter = new Formatter($this->translator);
        $this->assertTrue($formatter->formatInput($input));

        $dumper = new JSON();
        $this->assertEquals(json_encode(array(
            array('user' => array('single-values' => array('1'))),
            array('user' => array('single-values' => array('2'))))
        ), $dumper->dumpFilters($formatter));
    }

    public function testOneGroupTwoFields()
    {
        $input = new FilterQuery($this->translator);
        $input->setInput('user=1; invoice="F2012-800"');
        $input->setField('user', FilterConfig::create('user'));
        $input->setField('invoice', FilterConfig::create('invoice'));

        $formatter = new Formatter($this->translator);
        $this->assertTrue($formatter->formatInput($input));

        $dumper = new JSON();
        $this->assertEquals(json_encode(array(array('user' => array('single-values' => array('1')), 'invoice' => array('single-values' => array('F2012-800'))))), $dumper->dumpFilters($formatter));
    }

    public function testTwoGroupsTwoFields()
    {
        $input = new FilterQuery($this->translator);
        $input->setInput('(user=1; invoice="F2010-4242";),(user=2; invoice="F2012-4242";)');
        $input->setField('user', FilterConfig::create('user'));
        $input->setField('invoice', FilterConfig::create('invoice'));

        $formatter = new Formatter($this->translator);
        $this->assertTrue($formatter->formatInput($input));

        $dumper = new JSON();
        $this->assertEquals(json_encode(array(
            array('user' => array('single-values' => array('1')), 'invoice' => array('single-values' => array('F2010-4242'))),
            array('user' => array('single-values' => array('2')), 'invoice' => array('single-values' => array('F2012-4242'))))
        ), $dumper->dumpFilters($formatter));
    }

    public function testRangeValue()
    {
        $input = new FilterQuery($this->translator);
        $input->setInput('(user=1; invoice="F2010-4242"-"F2012-4242";),(user=2; invoice="F2012-4242";)');
        $input->setField('user', FilterConfig::create('user'));
        $input->setField('invoice', FilterConfig::create(null, null, false, true));

        $formatter = new Formatter($this->translator);
        $this->assertTrue($formatter->formatInput($input));

        $dumper = new JSON();
        $this->assertEquals(json_encode(array(
            array('user' => array('single-values' => array('1')), 'invoice' => array('ranges' => array('lower' => 'F2010-4242', 'higher' => 'F2012-4242'))),
            array('user' => array('single-values' => array('2')), 'invoice' => array('single-values' => array('F2012-4242'))))
        ), $dumper->dumpFilters($formatter));
    }

    public function testExcludedValue()
    {
        $input = new FilterQuery($this->translator);
        $input->setInput('(user=!1; invoice=!"F2010-4242"-"F2012-4242";),(user=2; invoice="F2012-4242";)');
        $input->setField('user', FilterConfig::create('user'));
        $input->setField('invoice', FilterConfig::create(null, null, false, true));

        $formatter = new Formatter($this->translator);
        $this->assertTrue($formatter->formatInput($input));

        $dumper = new JSON();
        $this->assertEquals(json_encode(array(
            array('user' => array('excluded-values' => array('1')), 'invoice' => array('excluded-ranges' => array('lower' => 'F2010-4242', 'higher' => 'F2012-4242'))),
            array('user' => array('single-values' => array('2')),   'invoice' => array('single-values'   => array('F2012-4242'))))
        ), $dumper->dumpFilters($formatter));
    }

    public function testCompareValue()
    {
        $input = new FilterQuery($this->translator);
        $input->setInput('(user=>1,<>2,>=5,<8,<=9;)');
        $input->setField('user', FilterConfig::create(null, null, false, false, true));
        $input->setField('invoice', FilterConfig::create('invoice'));

        $formatter = new Formatter($this->translator);
        $this->assertTrue($formatter->formatInput($input));

        $dumper = new JSON();
        $this->assertEquals(json_encode(array(
            array('user' => array('compares' => array(
                array('opr' => '>',  'value' => '1'),
                array('opr' => '<>', 'value' => '2'),
                array('opr' => '>=', 'value' => '5'),
                array('opr' => '<',  'value' => '8'),
                array('opr' => '<=', 'value' => '9'))))
        )), $dumper->dumpFilters($formatter));
    }
}
