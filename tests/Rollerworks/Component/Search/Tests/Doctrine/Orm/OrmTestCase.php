<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search\Tests\Doctrine\Orm;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Tests\OrmTestCase as OrmTestCaseBase;

class OrmTestCase extends OrmTestCaseBase
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    protected function setUp()
    {
        $this->em = $this->_getTestEntityManager();

        // Don't remember cache between runs
        $this->em->getConfiguration()->setQueryCacheImpl(new \Doctrine\Common\Cache\ArrayCache);

        $this->em->getConfiguration()->addCustomStringFunction('RW_SEARCH_FIELD_CONVERSION', 'Rollerworks\Component\Search\Doctrine\Orm\Functions\SqlFieldConversion');
        $this->em->getConfiguration()->addCustomStringFunction('RW_SEARCH_VALUE_CONVERSION', 'Rollerworks\Component\Search\Doctrine\Orm\Functions\SqlValueConversion');
        $this->em->getConfiguration()->addCustomStringFunction('RW_SEARCH_MATCH', 'Rollerworks\Component\Search\Doctrine\Orm\Functions\ValueMatch');
    }

    /**
     * @param array              $expected
     * @param Query|QueryBuilder $query
     */
    protected function assertQueryParamsEquals(array $expected, $query)
    {
        foreach ($expected as $name => $value) {
            $paramVal = $query->getParameter($name);
            $this->assertInstanceOf('Doctrine\ORM\Query\Parameter', $paramVal);
            $this->assertEquals($value, $query->getParameter($name)->getValue());
        }
    }

    /**
     * @param string $platform
     *
     * @return \Doctrine\DBAL\Connection
     */
    protected function getConnectionMock($platform)
    {
        $connectionMock = $this->getMock('Doctrine\DBAL\Connection', array(), array(), '', false);
        $connectionMock
                ->expects($this->any())
                ->method('getDatabasePlatform')
                ->will($this->returnValue($platform));

        return $connectionMock;
    }
}
