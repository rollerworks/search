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

namespace Rollerworks\RecordFilterBundle\Tests\Input;

use Rollerworks\RecordFilterBundle\Input\Query as QueryInput;

class QueryTest extends \PHPUnit_Framework_TestCase
{
    function testQuerySingleField()
    {
        $input = new QueryInput();
        $input->setQueryString('User=2');

        $this->assertEquals('User=2', $input->getQueryString());

        $this->assertEquals(array(array('user' => '2')), $input->getValues());
    }

    function testQuerySingleFieldWithSpaces()
    {
        $input = new QueryInput();
        $input->setQueryString('User = 2');

        $this->assertEquals(array(array('user' => '2')), $input->getValues());
    }

    function testQuerySingleFieldWithUnicode()
    {
        $input = new QueryInput();
        $input->setQueryString('ß = 2');

        $this->assertEquals(array(array('ß' => '2')), $input->getValues());
    }

    function testQueryMultipleFields()
    {
        $input = new QueryInput();
        $input->setQueryString('User=2; Status=Active');

        $this->assertEquals(array(array('user' => '2', 'status' => 'Active')), $input->getValues());
    }

    function testQueryMultipleFieldsNoSpace()
    {
        $input = new QueryInput();
        $input->setQueryString('User=2;Status=Active');

        $this->assertEquals(array(array('user' => '2', 'status' => 'Active')), $input->getValues());
    }

    // Field-name appears more then once
    function testQueryDoubleFields()
    {
        $input = new QueryInput();
        $input->setQueryString('User=2; Status=Active; User=3;');

        $this->assertEquals(array(array('user' => '2,3', 'status' => 'Active')), $input->getValues());
    }

    // Test the escaping of the filter-delimiter
    function testEscapedFilter()
    {
        $input = new QueryInput();
        $input->setQueryString('User=2; Status="Active;None"; date=29-10-2010');

        $this->assertEquals(array(array('user' => '2','status' => '"Active;None"','date' => '29-10-2010')), $input->getValues());
    }

    function testOrGroup()
    {
        $input = new QueryInput();
        $input->setQueryString('(User=2; Status="Active;None"; date=29-10-2010;),(User=3; Status=Concept; date=30-10-2010;)');

        $this->assertEquals(array(
                array(
                    'user'   => '2',
                    'status' => '"Active;None"',
                    'date'   => '29-10-2010'
                ),

                array(
                    'user'   => '3',
                    'status' => 'Concept',
                    'date'   => '30-10-2010'
                ),
            ),

            $input->getValues());

        $this->assertTrue($input->hasGroups());
    }

    function testOrGroupValueWithBars()
    {
        $input = new QueryInput();
        $input->setQueryString('(User=2; Status="(Active;None)"; date=29-10-2010;),(User=3; Status=Concept; date=30-10-2010;)');

        $this->assertEquals(array(
                array(
                    'user'      => '2',
                    'status'    => '"(Active;None)"',
                    'date'      => '29-10-2010'
                ),

                array(
                    'user'      => '3',
                    'status'    => 'Concept',
                    'date'      => '30-10-2010'
                ),
            ),

            $input->getValues()
        );

        $this->assertTrue( $input->hasGroups() );
    }

    function testQueryWithSectionPrefix()
    {
        $input = new QueryInput();
        $input->setQueryString('user,web; User=2; Status=Active');

        $this->assertEquals(array(array('user' => '2','status' => 'Active')), $input->getValues());

        $this->assertEquals(array('user', 'web'), $input->getSections());
    }

    function testQueryWithSectionPrefixDuplicate()
    {
        $input = new QueryInput();
        $input->setQueryString('user,web,user; User=2; Status=Active');

        $this->assertEquals(array(array(
            'user'   => '2',
            'status' => 'Active')), $input->getValues());

        $this->assertEquals(array('user', 'web'), $input->getSections());
    }
}