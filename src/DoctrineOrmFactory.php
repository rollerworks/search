<?php

/*
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Doctrine\Orm;

use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\NativeQuery;
use Doctrine\ORM\Query;
use Rollerworks\Component\Search\SearchConditionInterface;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class DoctrineOrmFactory
{
    /**
     * @var Cache
     */
    private $cacheDriver;

    /**
     * Constructor.
     *
     * @param Cache $cacheDriver
     */
    public function __construct(Cache $cacheDriver = null)
    {
        $this->cacheDriver = $cacheDriver;
    }

    /**
     * Creates a new WhereBuilder for the SearchCondition.
     *
     * Conversions are applied using the 'doctrine_dbal_conversion' option (when present).
     *
     * @param NativeQuery|Query        $query           Doctrine ORM (Native)Query object
     * @param SearchConditionInterface $searchCondition SearchCondition object
     *
     * @return NativeWhereBuilder|WhereBuilder
     */
    public function createWhereBuilder($query, SearchConditionInterface $searchCondition)
    {
        if ($query instanceof NativeQuery) {
            $whereBuilder = new NativeWhereBuilder($query, $searchCondition);
        } elseif ($query instanceof Query) {
            $whereBuilder = new WhereBuilder($query, $searchCondition);
        } else {
            throw new \InvalidArgumentException(
                sprintf('Query "%s" is not supported by the DoctrineOrmFactory.', get_class($query))
            );
        }

        foreach ($searchCondition->getFieldSet()->all() as $name => $field) {
            if (!$field->hasOption('doctrine_dbal_conversion')) {
                continue;
            }

            $conversion = $field->getOption('doctrine_dbal_conversion');

            // Lazy loaded
            if ($conversion instanceof \Closure) {
                $conversion = $conversion();
            }

            $whereBuilder->setConverter($name, $conversion);
        }

        return $whereBuilder;
    }

    /**
     * Creates a new CacheWhereBuilder instance for the given WhereBuilder.
     *
     * @param WhereBuilder|NativeWhereBuilder $whereBuilder
     * @param int                             $lifetime
     *
     * @throws \RuntimeException when no cache-driver is configured
     *
     * @return CacheWhereBuilder|CacheNativeWhereBuilder
     */
    public function createCacheWhereBuilder($whereBuilder, $lifetime = 0)
    {
        if (null === $this->cacheDriver) {
            throw new \RuntimeException('Unable to create CacheWhereBuilder, no CacheDriver is configured.');
        }

        if ($whereBuilder instanceof WhereBuilder) {
            return new CacheWhereBuilder($whereBuilder, $this->cacheDriver, $lifetime);
        } elseif ($whereBuilder instanceof NativeWhereBuilder) {
            return new CacheNativeWhereBuilder($whereBuilder, $this->cacheDriver, $lifetime);
        }

        throw new \InvalidArgumentException(
            sprintf('WhereBuilder "%s" is not supported by the DoctrineOrmFactory.', get_class($whereBuilder))
        );
    }
}
