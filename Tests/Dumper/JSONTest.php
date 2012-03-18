<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Rollerscapes
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link    http://projects.rollerscapes.net/RollerFramework
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */

namespace Rollerworks\RecordFilterBundle\Tests\Dumper;

use Rollerworks\RecordFilterBundle\Formatter\Formatter;
use Rollerworks\RecordFilterBundle\Input\Query;
use Rollerworks\RecordFilterBundle\Dumper\JSON;

class JSONTest extends \Rollerworks\RecordFilterBundle\Tests\Factory\FactoryTestCase
{
    function testFlattenedOneGroupOneField()
    {
        $formatter = new Formatter($this->translator);
        $formatter->addField('user');

        $this->assertTrue($formatter->formatInput(new Query('user=1;')));

        $dumper = new JSON();
        $this->assertEquals(json_encode(array(array('user' => array('1')))), $dumper->dumpFilters($formatter, true));
    }

    function testFlattenedTwoGroupsOneField()
    {
        $formatter = new Formatter($this->translator);
        $formatter->addField('user');

        $this->assertTrue($formatter->formatInput(new Query('(user=1;),(user=2;)')));

        $dumper = new JSON();
        $this->assertEquals(json_encode(array(
            array('user' => array('1')),
            array('user' => array('2')))
        ), $dumper->dumpFilters($formatter, true));
    }

    function testFlattenedFlattenedOneGroupTwoFields()
    {
        $formatter = new Formatter($this->translator);
        $formatter->addField('user');
        $formatter->addField('invoice');

        $this->assertTrue($formatter->formatInput(new Query('user=1; invoice="F2012-800";')));

        $dumper = new JSON();
        $this->assertEquals(json_encode(array(array('user' => array('1'), 'invoice' => array('F2012-800')))), $dumper->dumpFilters($formatter, true));
    }

    function testFlattenedTwoGroupsTwoFields()
    {
        $formatter = new Formatter($this->translator);
        $formatter->addField('user');
        $formatter->addField('invoice');

        $this->assertTrue($formatter->formatInput(new Query('(user=1; invoice="F2010-4242";),(user=2; invoice="F2012-4242";)')));

        $dumper = new JSON();
        $this->assertEquals(json_encode(array(
            array('user' => array('1'), 'invoice' => array('F2010-4242')),
            array('user' => array('2'), 'invoice' => array('F2012-4242')))
        ), $dumper->dumpFilters($formatter, true));
    }

    function testFlattenedRangeValue()
    {
        $formatter = new Formatter($this->translator);
        $formatter->addField('user');
        $formatter->addField('invoice', null, false, true);

        $this->assertTrue($formatter->formatInput(new Query('(user=1; invoice="F2010-4242"-"F2012-4242";),(user=2; invoice="F2012-4242";)')));

        $dumper = new JSON();
        $this->assertEquals(json_encode(array(
            array('user' => array('1'), 'invoice' => array('"F2010-4242"-"F2012-4242"')),
            array('user' => array('2'), 'invoice' => array('F2012-4242')))
        ), $dumper->dumpFilters($formatter, true));
    }

    function testFlattenedExcludedValue()
    {
        $formatter = new Formatter($this->translator);
        $formatter->addField('user');
        $formatter->addField('invoice', null, false, true);

        $this->assertTrue($formatter->formatInput(new Query('(user=!1; invoice=!"F2010-4242"-"F2012-4242";),(user=2; invoice="F2012-4242";)')));

        $dumper = new JSON();
        $this->assertEquals(json_encode(array(
            array('user' => array('!1'), 'invoice' => array('!"F2010-4242"-"F2012-4242"')),
            array('user' => array('2'), 'invoice' => array('F2012-4242')))
        ), $dumper->dumpFilters($formatter, true));
    }

    function testFlattenedCompareValue()
    {
        $formatter = new Formatter($this->translator);
        $formatter->addField('user', null, false, true, true);
        $formatter->addField('invoice');

        $this->assertTrue($formatter->formatInput(new Query('(user=>1,<>2,>=5,<8,<=9;)')));

        $dumper = new JSON();
        $this->assertEquals(json_encode(array(array('user' => array('>1', '<>2', '>=5', '<8', '<=9')))), $dumper->dumpFilters($formatter, true));
    }


    function testOneGroupOneField()
    {
        $formatter = new Formatter($this->translator);
        $formatter->addField('user');

        $this->assertTrue($formatter->formatInput(new Query('user=1;')));

        $dumper = new JSON();
        $this->assertEquals(json_encode(array(array('user' => array( 'single-values' => array('1'))))), $dumper->dumpFilters($formatter));
    }

    function testTwoGroupsOneField()
    {
        $formatter = new Formatter($this->translator);
        $formatter->addField('user');

        $this->assertTrue($formatter->formatInput(new Query('(user=1;),(user=2;)')));

        $dumper = new JSON();
        $this->assertEquals(json_encode(array(
            array('user' => array('single-values' => array('1'))),
            array('user' => array('single-values' => array('2'))))
        ), $dumper->dumpFilters($formatter));
    }

    function testOneGroupTwoFields()
    {
        $formatter = new Formatter($this->translator);
        $formatter->addField('user');
        $formatter->addField('invoice');

        $this->assertTrue($formatter->formatInput(new Query('user=1; invoice="F2012-800";')));

        $dumper = new JSON();
        $this->assertEquals(json_encode(array(array('user' => array('single-values' => array('1')), 'invoice' => array('single-values' => array('F2012-800'))))), $dumper->dumpFilters($formatter));
    }

    function testTwoGroupsTwoFields()
    {
        $formatter = new Formatter($this->translator);
        $formatter->addField('user');
        $formatter->addField('invoice');

        $this->assertTrue($formatter->formatInput(new Query('(user=1; invoice="F2010-4242";),(user=2; invoice="F2012-4242";)')));

        $dumper = new JSON();
        $this->assertEquals(json_encode(array(
            array('user' => array('single-values' => array('1')), 'invoice' => array('single-values' => array('F2010-4242'))),
            array('user' => array('single-values' => array('2')), 'invoice' => array('single-values' => array('F2012-4242'))))
        ), $dumper->dumpFilters($formatter));
    }

    function testRangeValue()
    {
        $formatter = new Formatter($this->translator);
        $formatter->addField('user');
        $formatter->addField('invoice', null, false, true);

        $this->assertTrue($formatter->formatInput(new Query('(user=1; invoice="F2010-4242"-"F2012-4242";),(user=2; invoice="F2012-4242";)')));

        $dumper = new JSON();
        $this->assertEquals(json_encode(array(
            array('user' => array('single-values' => array('1')), 'invoice' => array('ranges' => array('lower' => 'F2010-4242', 'higher' => 'F2012-4242'))),
            array('user' => array('single-values' => array('2')), 'invoice' => array('single-values' => array('F2012-4242'))))
        ), $dumper->dumpFilters($formatter));
    }

    function testExcludedValue()
    {
        $formatter = new Formatter($this->translator);
        $formatter->addField('user');
        $formatter->addField('invoice', null, false, true);

        $this->assertTrue($formatter->formatInput(new Query('(user=!1; invoice=!"F2010-4242"-"F2012-4242";),(user=2; invoice="F2012-4242";)')));

        $dumper = new JSON();
        $this->assertEquals(json_encode(array(
            array('user' => array('excluded-values' => array('1')), 'invoice' => array('excluded-ranges' => array('lower' => 'F2010-4242', 'higher' => 'F2012-4242'))),
            array('user' => array('single-values' => array('2')),   'invoice' => array('single-values'   => array('F2012-4242'))))
        ), $dumper->dumpFilters($formatter));
    }

    function testCompareValue()
    {
        $formatter = new Formatter($this->translator);
        $formatter->addField('user', null, false, true, true);
        $formatter->addField('invoice');

        $this->assertTrue($formatter->formatInput(new Query('(user=>1,<>2,>=5,<8,<=9;)')));

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
