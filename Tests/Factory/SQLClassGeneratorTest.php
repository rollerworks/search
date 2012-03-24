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

namespace Rollerworks\RecordFilterBundle\Tests\Factory;

use Rollerworks\RecordFilterBundle\Factory\FormatterFactory;
use Rollerworks\RecordFilterBundle\Factory\SQLStructFactory;
use Rollerworks\RecordFilterBundle\Formatter\FilterConfig;
use Rollerworks\RecordFilterBundle\Input\Query as QueryInput;

/**
 * Test the Validation generator. Its work is generating on-the-fly subclasses of a given model.
 * As you may have guessed, this is based on the Doctrine\ORM\Proxy module.
 */
class SQLClassGeneratorTest extends FactoryTestCase
{
    /**
     * @var \Doctrine\DBAL\Connection
     */
    protected static $connection = null;

    /**
     * @var \Rollerworks\RecordFilterBundle\Factory\SQLStructFactory
     */
    protected $SQLFactory;

    protected function setUp()
    {
        parent::setUp();

        // Keep an static reference to prevent an large connection counting
        if (self::$connection === null) {
            $params = array('driver'        => 'pdo_pgsql',
                            'host'          => 'localhost',
                            'db'            => 'rollerframework',
                            'user'          => 'rollerframework',
                            'password'      => 'rollerframework');

            self::$connection = \Doctrine\DBAL\DriverManager::getConnection($params);
        }

        $this->SQLFactory = new SQLStructFactory($this->annotationReader, __DIR__ . '/_generated', 'RecordFilter', true);
        $this->SQLFactory->setDBConnection(self::$connection);
    }

    function testOneField()
    {
        $formatter = $this->getFormatter('ECommerceProductSimple');

        $SQLStruct = $this->SQLFactory->getSQLStruct($formatter);

        $SQLExpected = '(id IN(2))';

        $input = new QueryInput();
        $input->setQueryString('id=2;');

        $this->assertTrue($formatter->formatInput($input));
        $this->assertEquals($SQLExpected, trim($SQLStruct->getWhereClause()));
    }

    function testOneFieldNoFilters()
    {
        $formatter = $this->getFormatter('ECommerceProductSimple');

        $SQLStruct = $this->SQLFactory->getSQLStruct($formatter);

        $SQLExpected = '';

        $input = new QueryInput();
        $input->setQueryString('(user=2;),(user=2;)');

        $this->assertTrue($formatter->formatInput($input));
        $this->assertEquals($SQLExpected, trim($SQLStruct->getWhereClause()));
    }

    function testOneFieldWithOrGroups()
    {
        $formatter = $this->getFormatter('ECommerceProductSimple');

        $SQLStruct = $this->SQLFactory->getSQLStruct($formatter);

        $SQLExpected = "(id IN(2))\n OR (id IN(3))";

        $input = new QueryInput();
        $input->setQueryString('(id=2;),(id=3;)');

        $this->assertTrue($formatter->formatInput($input));
        $this->assertEquals($SQLExpected, trim($SQLStruct->getWhereClause()));
    }


    function testTwoFields()
    {
        $formatter = $this->getFormatter('ECommerceProductTwo');

        $SQLStruct = $this->SQLFactory->getSQLStruct($formatter);

        $SQLExpected = "(id IN(2) AND name IN('Fab'))";
        $input = new QueryInput();

        $input->setQueryString('id=2;name=Fab');

        $this->assertTrue($formatter->formatInput($input));
        $this->assertEquals($SQLExpected, trim($SQLStruct->getWhereClause()));
    }

    function testAlias()
    {
        $formatter = $this->getFormatter('ECommerceProductTwo');

        $SQLStruct = $this->SQLFactory->getSQLStruct($formatter);

        $SQLExpected = "(user IN(2) AND name IN('Fab'))";
        $input = new QueryInput();

        $input->setQueryString('id=2;name=Fab;');
        $this->assertTrue($formatter->formatInput($input));

        $SQLStruct->setFieldAlias('id', 'user');
        $this->assertEquals($SQLExpected, trim($SQLStruct->getWhereClause()));
    }

    function testAliasByEngine()
    {
        $formatter = $this->getFormatter('ECommerceProductTwo');

        $SQLStruct = $this->SQLFactory->getSQLStruct($formatter);

        $SQLExpected = "(user IN(2) AND name IN('Fab'))";
        $input = new QueryInput();

        $input->setQueryString('id=2;name=Fab;');
        $this->assertTrue($formatter->formatInput($input));

        $SQLStruct->setFieldAlias('id', 'user', 'pdo_pgsql');
        $SQLStruct->setFieldAlias('id', 'user2'); // Explicit prevails above 'all'
        $SQLStruct->setFieldAlias('name', 'username', 'pdo_mysql');

        $this->assertEquals($SQLExpected, trim($SQLStruct->getWhereClause()));
    }

    function testCasting()
    {
        $formatter = $this->getFormatter('ECommerceProductTwo');

        $SQLStruct = $this->SQLFactory->getSQLStruct($formatter);

        $SQLExpected = "(id IN(2) AND name IN(CAST('Fab' AS TEXT)))";
        $input = new QueryInput();

        $input->setQueryString('id=2;name=Fab;');
        $this->assertTrue($formatter->formatInput($input));

        $SQLStruct->setFieldCast('name', 'TEXT');
        $SQLStruct->setFieldCast('id', 'USER', 'pdo_mysql');

        $this->assertEquals($SQLExpected, trim($SQLStruct->getWhereClause()));
    }

    function testCastingAndAlias()
    {
        $formatter = $this->getFormatter('ECommerceProductTwo');

        $SQLStruct = $this->SQLFactory->getSQLStruct($formatter);

        $SQLExpected = "(user IN(CAST('me' AS USER_T)) AND name IN(CAST('Fab' AS TEXT)))";
        $input = new QueryInput();

        $input->setQueryString('id=me;name=Fab;');
        $this->assertTrue($formatter->formatInput($input));

        $SQLStruct->setFieldAlias('id', 'user', 'pdo_pgsql');

        $SQLStruct->setFieldCast('name', 'TEXT');
        $SQLStruct->setFieldCast('id', 'USER_T', 'pdo_pgsql');

        $this->assertEquals($SQLExpected, trim($SQLStruct->getWhereClause()));
    }


    function testFloat()
    {
        $formatter = $this->getFormatter('ECommerceProductTwo');

        $SQLStruct = $this->SQLFactory->getSQLStruct($formatter);

        $SQLExpected = "(id IN(2.1) AND name IN('Fab'))";
        $input = new QueryInput();

        $input->setQueryString('id=2.1;name=Fab;');

        $this->assertTrue($formatter->formatInput($input));
        $this->assertEquals($SQLExpected, trim($SQLStruct->getWhereClause()));
    }


    function testTwoFieldsWithOrGroups()
    {
        $formatter = $this->getFormatter('ECommerceProductTwo');

        $SQLStruct = $this->SQLFactory->getSQLStruct($formatter);

        $SQLExpected = "(id IN(2) AND name IN('Fab'))\n OR (id IN(3))";

        $input = new QueryInput();
        $input->setQueryString('(id=2;name=Fab;),(id=3;)');

        $this->assertTrue($formatter->formatInput($input));
        $this->assertEquals($SQLExpected, trim($SQLStruct->getWhereClause()));
    }

    function testTwoFieldsWithRanges()
    {
        $formatter = $this->getFormatter('ECommerceProductRange');

        $SQLStruct = $this->SQLFactory->getSQLStruct($formatter);

        $SQLExpected = "(id IN(3) AND id BETWEEN 5 AND 20 AND name IN('Fab'))";

        $input = new QueryInput();
        $input->setQueryString('id=3,5-20;name=Fab;');

        $this->assertTrue($formatter->formatInput($input));
        $this->assertEquals($SQLExpected, trim($SQLStruct->getWhereClause()));
    }

    function testTwoFieldsWithExcludedRanges()
    {
        $formatter = $this->getFormatter('ECommerceProductRange');

        $SQLStruct = $this->SQLFactory->getSQLStruct($formatter);

        $SQLExpected = "(id IN(3) AND id BETWEEN 10 AND 30 AND id NOT BETWEEN 15 AND 20 AND name IN('Fab'))";

        $input = new QueryInput();
        $input->setQueryString('id=3,10-30,!15-20;name=Fab;');

        $this->assertTrue($formatter->formatInput($input));
        $this->assertEquals($SQLExpected, trim($SQLStruct->getWhereClause()));
    }

    function testTwoFieldsWithCompares()
    {
        $formatter = $this->getFormatter('ECommerceProductCompares');

        $SQLStruct = $this->SQLFactory->getSQLStruct($formatter);

        $SQLExpected = "(id IN(3) AND id > 5 AND name IN('Fab'))";

        $input = new QueryInput();
        $input->setQueryString('id=3,>5;name=Fab;');

        $this->assertTrue($formatter->formatInput($input));
        $this->assertEquals($SQLExpected, trim($SQLStruct->getWhereClause()));
    }

    function testGenerateClasses()
    {
        $this->assertFileNotExists(__DIR__ . '/_generated/RollerworksRecordFilterBundleTestsFixturesBaseBundleEntityECommerceECommerceProductSimple/SQLStruct.php');
        $this->assertFileNotExists(__DIR__ . '/_generated/RollerworksFrameworkBundleTestsRecordFilterFixturesBaseBundleEntityECommerceECommerceProductCompares/SQLStruct.php');

        $this->SQLFactory->generateClasses(array(
            'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\BaseBundle\\Entity\\ECommerce\\ECommerceProductSimple',
            'Rollerworks\\RecordFilterBundle\\Tests\\Fixtures\\BaseBundle\\Entity\\ECommerce\\ECommerceProductCompares'
        ));

        $this->assertFileExists(__DIR__ . '/_generated/RollerworksRecordFilterBundleTestsFixturesBaseBundleEntityECommerceECommerceProductSimple/SQLStruct.php');
        $this->assertFileExists(__DIR__ . '/_generated/RollerworksRecordFilterBundleTestsFixturesBaseBundleEntityECommerceECommerceProductCompares/SQLStruct.php');
    }
}
