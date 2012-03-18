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