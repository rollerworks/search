<?php

/**
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests\Doctrine\Orm;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Rollerworks\Component\Search\Tests\Doctrine\Dbal\DbalTestCase;

class OrmTestCase extends DbalTestCase
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    private static $doctrineAnnotationsDir;

    protected function setUp()
    {
        $this->em = $this->_getTestEntityManager();

        // Don't remember cache between runs
        $this->em->getConfiguration()->setQueryCacheImpl(new ArrayCache);

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
        foreach ($expected as $name => $param) {
            if (is_array($param)) {
                list(, $value)=$param;
            } else {
                $value = $param;
            }

            $paramVal = $query->getParameter($name);
            $this->assertInstanceOf('Doctrine\ORM\Query\Parameter', $paramVal);
            $this->assertEquals($value, $query->getParameter($name)->getValue());
        }
    }

    /**
     * The metadata cache that is shared between all ORM tests (except functional tests).
     *
     * @var \Doctrine\Common\Cache\Cache|null
     */
    private static $metadataCacheImpl = null;

    /**
     * The query cache that is shared between all ORM tests (except functional tests).
     *
     * @var \Doctrine\Common\Cache\Cache|null
     */
    private static $queryCacheImpl = null;

    /**
     * @param array $paths
     * @param mixed $alias
     *
     * @return \Doctrine\ORM\Mapping\Driver\AnnotationDriver
     */
    protected function createAnnotationDriver($paths = array(), $alias = null)
    {
        if (version_compare(\Doctrine\Common\Version::VERSION, '3.0.0', '>=')) {
            $reader = new \Doctrine\Common\Annotations\CachedReader(
                new \Doctrine\Common\Annotations\AnnotationReader(), new ArrayCache()
            );
        } else {
            // Register the ORM Annotations in the AnnotationRegistry
            $reader = new \Doctrine\Common\Annotations\SimpleAnnotationReader();
            $reader->addNamespace('Doctrine\ORM\Mapping');
            $reader = new \Doctrine\Common\Annotations\CachedReader($reader, new ArrayCache());
        }

        if (!self::$doctrineAnnotationsDir) {
            $r = new \ReflectionClass('Doctrine\ORM\Mapping\Driver\AnnotationDriver');
            self::$doctrineAnnotationsDir = dirname($r->getFileName());

        }

        \Doctrine\Common\Annotations\AnnotationRegistry::registerFile(
            self::$doctrineAnnotationsDir."/DoctrineAnnotations.php");

        return new \Doctrine\ORM\Mapping\Driver\AnnotationDriver($reader, (array) $paths);
    }

    /**
     * Creates an EntityManager for testing purposes.
     *
     * NOTE: The created EntityManager will have its dependant DBAL parts completely
     * mocked out using a DriverMock, ConnectionMock, etc. These mocks can then
     * be configured in the tests to simulate the DBAL behavior that is desired
     * for a particular test,
     *
     * @param \Doctrine\DBAL\Connection|array    $conn
     * @param mixed                              $conf
     * @param \Doctrine\Common\EventManager|null $eventManager
     * @param bool                               $withSharedMetadata
     *
     * @return \Doctrine\ORM\EntityManager
     */
    protected function _getTestEntityManager($conn = null, $conf = null, $eventManager = null, $withSharedMetadata = true)
    {
        $metadataCache = $withSharedMetadata
            ? self::getSharedMetadataCacheImpl()
            : new ArrayCache;

        $config = new \Doctrine\ORM\Configuration();

        $config->setMetadataCacheImpl($metadataCache);
        $config->setMetadataDriverImpl($config->newDefaultAnnotationDriver(array(), true));
        $config->setQueryCacheImpl(self::getSharedQueryCacheImpl());
        $config->setProxyDir(__DIR__.'/Proxies');
        $config->setProxyNamespace('Doctrine\Tests\Proxies');

        if ($conn === null) {
            $conn = array(
                'driverClass'  => 'Doctrine\Tests\Mocks\DriverMock',
                'wrapperClass' => 'Doctrine\Tests\Mocks\ConnectionMock',
                'user'         => 'john',
                'password'     => 'wayne'
            );
        }

        if (is_array($conn)) {
            $conn = \Doctrine\DBAL\DriverManager::getConnection($conn, $config, $eventManager);
        }

        return \Doctrine\Tests\Mocks\EntityManagerMock::create($conn, $config, $eventManager);
    }

    /**
     * @return \Doctrine\Common\Cache\Cache
     */
    private static function getSharedMetadataCacheImpl()
    {
        if (self::$metadataCacheImpl === null) {
            self::$metadataCacheImpl = new ArrayCache();
        }

        return self::$metadataCacheImpl;
    }

    /**
     * @return \Doctrine\Common\Cache\Cache
     */
    private static function getSharedQueryCacheImpl()
    {
        if (self::$queryCacheImpl === null) {
            self::$queryCacheImpl = new ArrayCache();
        }

        return self::$queryCacheImpl;
    }
}
