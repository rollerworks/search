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
use Rollerworks\RecordFilterBundle\Dumper\JSON;

class JSONTest extends \Rollerworks\RecordFilterBundle\Tests\TestCase
{
    function testFlattenedOneGroupOneField()
    {
        $input = new FilterQuery('user=1;');
        $input->setField('user');

        $formatter = new Formatter($this->translator);
        $this->assertTrue($formatter->formatInput($input));

        $dumper = new JSON();
        $this->assertEquals(json_encode(array(array('user' => array('1')))), $dumper->dumpFilters($formatter, true));
    }

    function testFlattenedTwoGroupsOneField()
    {
        $input = new FilterQuery('(user=1;),(user=2;)');
        $input->setField('user');

        $formatter = new Formatter($this->translator);
        $this->assertTrue($formatter->formatInput($input));

        $dumper = new JSON();
        $this->assertEquals(json_encode(array(
            array('user' => array('1')),
            array('user' => array('2')))
        ), $dumper->dumpFilters($formatter, true));
    }

    function testFlattenedFlattenedOneGroupTwoFields()
    {
        $input = new FilterQuery('user=1; invoice="F2012-800";');
        $input->setField('user');
        $input->setField('invoice');

        $formatter = new Formatter($this->translator);
        $this->assertTrue($formatter->formatInput($input));

        $dumper = new JSON();
        $this->assertEquals(json_encode(array(array('user' => array('1'), 'invoice' => array('F2012-800')))), $dumper->dumpFilters($formatter, true));
    }

    function testFlattenedTwoGroupsTwoFields()
    {
        $input = new FilterQuery('(user=1; invoice="F2010-4242";),(user=2; invoice="F2012-4242";)');
        $input->setField('user');
        $input->setField('invoice');

        $formatter = new Formatter($this->translator);
        $this->assertTrue($formatter->formatInput($input));

        $dumper = new JSON();
        $this->assertEquals(json_encode(array(
            array('user' => array('1'), 'invoice' => array('F2010-4242')),
            array('user' => array('2'), 'invoice' => array('F2012-4242')))
        ), $dumper->dumpFilters($formatter, true));
    }

    function testFlattenedRangeValue()
    {
        $input = new FilterQuery('(user=1; invoice="F2010-4242"-"F2012-4242";),(user=2; invoice="F2012-4242";)');
        $input->setField('user');
        $input->setField('invoice', null, null, false, true);

        $formatter = new Formatter($this->translator);
        $this->assertTrue($formatter->formatInput($input));

        $dumper = new JSON();
        $this->assertEquals(json_encode(array(
            array('user' => array('1'), 'invoice' => array('"F2010-4242"-"F2012-4242"')),
            array('user' => array('2'), 'invoice' => array('F2012-4242')))
        ), $dumper->dumpFilters($formatter, true));
    }

    function testFlattenedExcludedValue()
    {
        $input = new FilterQuery('(user=!1; invoice=!"F2010-4242"-"F2012-4242";),(user=2; invoice="F2012-4242";)');
        $input->setField('user');
        $input->setField('invoice', null, null, false, true);

        $formatter = new Formatter($this->translator);
        $this->assertTrue($formatter->formatInput($input));

        $dumper = new JSON();
        $this->assertEquals(json_encode(array(
            array('user' => array('!1'), 'invoice' => array('!"F2010-4242"-"F2012-4242"')),
            array('user' => array('2'), 'invoice' => array('F2012-4242')))
        ), $dumper->dumpFilters($formatter, true));
    }

    function testFlattenedCompareValue()
    {
        $input = new FilterQuery('(user=>1,<>2,>=5,<8,<=9;)');
        $input->setField('user', null, null, false, true, true);
        $input->setField('invoice');

        $formatter = new Formatter($this->translator);
        $this->assertTrue($formatter->formatInput($input));

        $dumper = new JSON();
        $this->assertEquals(json_encode(array(array('user' => array('>1', '<>2', '>=5', '<8', '<=9')))), $dumper->dumpFilters($formatter, true));
    }


    function testOneGroupOneField()
    {
        $input = new FilterQuery('(user=1;)');
        $input->setField('user');

        $formatter = new Formatter($this->translator);
        $this->assertTrue($formatter->formatInput($input));

        $dumper = new JSON();
        $this->assertEquals(json_encode(array(array('user' => array( 'single-values' => array('1'))))), $dumper->dumpFilters($formatter));
    }

    function testTwoGroupsOneField()
    {
        $input = new FilterQuery('(user=1;),(user=2;)');
        $input->setField('user');

        $formatter = new Formatter($this->translator);
        $this->assertTrue($formatter->formatInput($input));

        $dumper = new JSON();
        $this->assertEquals(json_encode(array(
            array('user' => array('single-values' => array('1'))),
            array('user' => array('single-values' => array('2'))))
        ), $dumper->dumpFilters($formatter));
    }

    function testOneGroupTwoFields()
    {
        $input = new FilterQuery('user=1; invoice="F2012-800"');
        $input->setField('user');
        $input->setField('invoice');

        $formatter = new Formatter($this->translator);
        $this->assertTrue($formatter->formatInput($input));

        $dumper = new JSON();
        $this->assertEquals(json_encode(array(array('user' => array('single-values' => array('1')), 'invoice' => array('single-values' => array('F2012-800'))))), $dumper->dumpFilters($formatter));
    }

    function testTwoGroupsTwoFields()
    {
        $input = new FilterQuery('(user=1; invoice="F2010-4242";),(user=2; invoice="F2012-4242";)');
        $input->setField('user');
        $input->setField('invoice');

        $formatter = new Formatter($this->translator);
        $this->assertTrue($formatter->formatInput($input));


        $dumper = new JSON();
        $this->assertEquals(json_encode(array(
            array('user' => array('single-values' => array('1')), 'invoice' => array('single-values' => array('F2010-4242'))),
            array('user' => array('single-values' => array('2')), 'invoice' => array('single-values' => array('F2012-4242'))))
        ), $dumper->dumpFilters($formatter));
    }

    function testRangeValue()
    {
        $input = new FilterQuery('(user=1; invoice="F2010-4242"-"F2012-4242";),(user=2; invoice="F2012-4242";)');
        $input->setField('user');
        $input->setField('invoice', null, null, false, true);

        $formatter = new Formatter($this->translator);
        $this->assertTrue($formatter->formatInput($input));

        $dumper = new JSON();
        $this->assertEquals(json_encode(array(
            array('user' => array('single-values' => array('1')), 'invoice' => array('ranges' => array('lower' => 'F2010-4242', 'higher' => 'F2012-4242'))),
            array('user' => array('single-values' => array('2')), 'invoice' => array('single-values' => array('F2012-4242'))))
        ), $dumper->dumpFilters($formatter));
    }

    function testExcludedValue()
    {
        $input = new FilterQuery('(user=!1; invoice=!"F2010-4242"-"F2012-4242";),(user=2; invoice="F2012-4242";)');
        $input->setField('user');
        $input->setField('invoice', null, null, false, true);

        $formatter = new Formatter($this->translator);
        $this->assertTrue($formatter->formatInput($input));

        $dumper = new JSON();
        $this->assertEquals(json_encode(array(
            array('user' => array('excluded-values' => array('1')), 'invoice' => array('excluded-ranges' => array('lower' => 'F2010-4242', 'higher' => 'F2012-4242'))),
            array('user' => array('single-values' => array('2')),   'invoice' => array('single-values'   => array('F2012-4242'))))
        ), $dumper->dumpFilters($formatter));
    }

    function testCompareValue()
    {
        $input = new FilterQuery('(user=>1,<>2,>=5,<8,<=9;)');
        $input->setField('user', null, null, false, false, true);
        $input->setField('invoice');

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
