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

namespace Rollerworks\RecordFilterBundle\Tests;

use Rollerworks\RecordFilterBundle\FilterStruct;
use Rollerworks\RecordFilterBundle\Struct\Compare;
use Rollerworks\RecordFilterBundle\Struct\Range;
use Rollerworks\RecordFilterBundle\Struct\Value;


class FilterStructTest extends \PHPUnit_Framework_TestCase
{
    function testLabel()
    {
        $struct = new FilterStruct('test', 'none');

        $this->assertEquals('test', $struct->getLabel());
    }

    function testInput()
    {
        $struct = new FilterStruct('test', 'none');

        $this->assertEquals('none', $struct->getOriginalInput());
    }

    function testLooseValues()
    {
        $struct = new FilterStruct('test', 'none', array(new Value('4')));

        $this->assertTrue($struct->hasSingleValues());
        $this->assertEquals(array(new Value('4')), $struct->getSingleValues());
    }

    function testExcludes()
    {
        $struct = new FilterStruct('test', 'none', array(), array(new Value('4')));

        $this->assertTrue($struct->hasExcludes());
        $this->assertEquals(array(new Value('4')), $struct->getExcludes());
    }

    function testRanges()
    {
        $struct = new FilterStruct('test', 'none', array(), array(), array(new Range(10, 100)), array());

        $this->assertTrue($struct->hasRanges());
        $this->assertEquals(array(new Range(10, 100)), $struct->getRanges());
    }

    function testExcludedRanges()
    {
        $struct = new FilterStruct('test', 'none', array(), array(), array(new Range(10, 100)), array(), array(new Range(12, 20)));

        $this->assertTrue($struct->hasRanges());
        $this->assertTrue($struct->hasExcludedRanges());

        $this->assertEquals(array(new Range(10, 100)), $struct->getRanges());
        $this->assertEquals(array(new Range(12, 20)), $struct->getExcludedRanges());
    }

    function testCompares()
    {
        $struct = new FilterStruct('test', 'none', array(), array(), array(), array(new Compare(10, '>')));

        $this->assertTrue($struct->hasCompares());
        $this->assertEquals(array(new Compare(10, '>')), $struct->getCompares());
    }


    function testUnsetLooseValue()
    {
        $struct = new FilterStruct('test', 'none', array(0 => new Value('4'), 4 => new Value('4')), array(1 => new Value('10'), 5 => new Value('20')), array(2 => new Range(10, 100), 6 => new Range(110, 200)), array(3 => new Compare(10, '>'), 7 => new Compare(20, '<')));

        $this->assertEquals(array(0 => new Value('4'), 4 => new Value('4')), $struct->getSingleValues());
        $this->assertEquals(array(1 => new Value('10'), 5 => new Value('20')), $struct->getExcludes());
        $this->assertEquals(array(2 => new Range(10, 100), 6 => new Range(110, 200)), $struct->getRanges());
        $this->assertEquals(array(3 => new Compare(10, '>'), 7 => new Compare(20, '<')), $struct->getCompares());

        $struct->removeSingleValue(4);

        $this->assertTrue($struct->hasSingleValues());
        $this->assertEquals(array(0 => new Value('4')), $struct->getSingleValues());
    }

    function testUnsetExclude()
    {
        $struct = new FilterStruct('test', 'none', array(0 => new Value('4'), 4 => new Value('4')), array(1 => new Value('10'), 5 => new Value('20')), array(2 => new Range(10, 100), 6 => new Range(110, 200)), array(3 => new Compare(10, '>'), 7 => new Compare(20, '<')));

        $this->assertEquals(array(0 => new Value('4'), 4 => new Value('4')), $struct->getSingleValues());
        $this->assertEquals(array(1 => new Value('10'), 5 => new Value('20')), $struct->getExcludes());
        $this->assertEquals(array(2 => new Range(10, 100), 6 => new Range(110, 200)), $struct->getRanges());
        $this->assertEquals(array(3 => new Compare(10, '>'), 7 => new Compare(20, '<')), $struct->getCompares());

        $struct->removeExclude(5);

        $this->assertTrue($struct->hasExcludes());
        $this->assertEquals(array(1 => new Value('10')), $struct->getExcludes());
    }

    function testUnsetRange()
    {
        $struct = new FilterStruct('test', 'none', array(0 => new Value('4'), 4 => new Value('4')), array(1 => new Value('10'), 5 => new Value('20')), array(2 => new Range(10, 100), 6 => new Range(110, 200)), array(3 => new Compare(10, '>'), 7 => new Compare(20, '<')));

        $this->assertEquals(array(0 => new Value('4'), 4 => new Value('4')), $struct->getSingleValues());
        $this->assertEquals(array(1 => new Value('10'), 5 => new Value('20')), $struct->getExcludes());
        $this->assertEquals(array(2 => new Range(10, 100), 6 => new Range(110, 200)), $struct->getRanges());
        $this->assertEquals(array(3 => new Compare(10, '>'), 7 => new Compare(20, '<')), $struct->getCompares());
        $struct->removeRange(6);

        $this->assertTrue($struct->hasRanges());
        $this->assertEquals(array(2 => new Range(10, 100)), $struct->getRanges());
    }

    function testUnsetExcludedRange()
    {
        $struct = new FilterStruct('test', 'none', array(0 => new Value('4'), 4 => new Value('4')), array(1 => new Value('10'), 5 => new Value('20')), array(2 => new Range(10, 100), 6 => new Range(110, 200)), array(3 => new Compare(10, '>'), 7 => new Compare(20, '<')), array(8 => new Range(12, 15)));

        $this->assertEquals(array(0 => new Value('4'), 4 => new Value('4')), $struct->getSingleValues());
        $this->assertEquals(array(1 => new Value('10'), 5 => new Value('20')), $struct->getExcludes());
        $this->assertEquals(array(2 => new Range(10, 100), 6 => new Range(110, 200)), $struct->getRanges());
        $this->assertEquals(array(3 => new Compare(10, '>'), 7 => new Compare(20, '<')), $struct->getCompares());
        $this->assertEquals(array(8 => new Range(12, 15)), $struct->getExcludedRanges());

        $struct->removeExcludedRange(8);

        $this->assertTrue($struct->hasRanges());
        $this->assertEquals(array(2 => new Range(10, 100), 6 => new Range(110, 200)), $struct->getRanges());

        $this->assertFalse($struct->hasExcludedRanges());
        $this->assertEquals(array(), $struct->getExcludedRanges());
    }

    function testUnsetCompare()
    {
        $struct = new FilterStruct('test', 'none', array(0 => new Value('4'), 4 => new Value('4')), array(1 => new Value('10'), 5 => new Value('20')), array(2 => new Range(10, 100), 6 => new Range(110, 200)), array(3 => new Compare(10, '>'), 7 => new Compare(20, '<')));

        $this->assertEquals(array(0 => new Value('4'), 4 => new Value('4')), $struct->getSingleValues());
        $this->assertEquals(array(1 => new Value('10'), 5 => new Value('20')), $struct->getExcludes());
        $this->assertEquals(array(2 => new Range(10, 100), 6 => new Range(110, 200)), $struct->getRanges());
        $this->assertEquals(array(3 => new Compare(10, '>'), 7 => new Compare(20, '<')), $struct->getCompares());

        $struct->removeCompare(7);

        $this->assertTrue($struct->hasCompares());
        $this->assertEquals(array(3 => new Compare(10, '>')), $struct->getCompares());
    }
}