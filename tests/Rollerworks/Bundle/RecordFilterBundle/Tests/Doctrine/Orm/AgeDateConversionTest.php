<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Tests\Doctrine\Orm;

use Doctrine\DBAL\Types\Type as DBALType;
use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Platforms\MySqlPlatform;
use Rollerworks\Bundle\RecordFilterBundle\Type\DateTimeExtended;
use Rollerworks\Bundle\RecordFilterBundle\Doctrine\Orm\Conversion\AgeDateConversion;

class AgeDateConversionTest extends OrmTestCase
{
    /**
     * @dataProvider provideStrategyTests
     *
     * @param mixed        $value
     * @param string       $type
     * @param null|integer $expectedStrategy
     */
    public function testStrategy($value, $type, $expectedStrategy)
    {
        $conversion = new AgeDateConversion();

        $this->assertEquals($expectedStrategy, $conversion->getConversionStrategy($value, DBALType::getType($type), $this->em->getConnection()));
    }

    public function testConvertFieldSql()
    {
        $conversion = new AgeDateConversion();

        $this->assertEquals(
            "to_char('YYYY', age(birthday))",
            $conversion->getConvertFieldSql('birthday', DBALType::getType('date'), $this->getConnectionMock(new PostgreSqlPlatform()), array('__conversion_strategy' => 1))
        );

        $this->assertEquals(
            "(DATE_FORMAT(NOW(), '%Y') - DATE_FORMAT(birthday, '%Y') - (DATE_FORMAT(NOW(), '00-%m-%d') < DATE_FORMAT(birthday, '00-%m-%d')))",
            $conversion->getConvertFieldSql('birthday', DBALType::getType('date'), $this->getConnectionMock(new MySqlPlatform()), array('__conversion_strategy' => 1))
        );

        $this->assertEquals(
            "birthday",
            $conversion->getConvertFieldSql('birthday', DBALType::getType('date'), $this->getConnectionMock(new MySqlPlatform()), array('__conversion_strategy' => 2))
        );

        $this->assertEquals(
            "CAST(birthday AS DATE)",
            $conversion->getConvertFieldSql('birthday', DBALType::getType('datetime'), $this->getConnectionMock(new MySqlPlatform()), array('__conversion_strategy' => 3))
        );
    }

    public function testConvertValue()
    {
        $conversion = new AgeDateConversion();

        $this->assertEquals(
            '32',
            $conversion->convertValue('32', DBALType::getType('date'), $this->getConnectionMock(new PostgreSqlPlatform()), array('__conversion_strategy' => 1))
        );

        $this->assertEquals(
            '1990-05-30',
            $conversion->convertValue(new \DateTime('1990-05-30'), DBALType::getType('date'), $this->getConnectionMock(new MySqlPlatform()), array('__conversion_strategy' => 2))
        );

        $this->assertEquals(
            '1990-05-30',
            $conversion->convertValue(new \DateTime('1990-05-30 03:15'), DBALType::getType('datetime'), $this->getConnectionMock(new MySqlPlatform()), array('__conversion_strategy' => 3))
        );
    }

    public function provideStrategyTests()
    {
        return array(
            array(12, 'date', 1),
            array(12, 'datetime', 1),
            array('12', 'date', 1),
            array('12', 'datetime', 1),

            array(new DateTimeExtended('1990-05-30'), 'date', 2),
            array(new DateTimeExtended('1990-05-30'), 'datetime', 3),
        );
    }
}
