<?php

declare(strict_types=1);

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Doctrine\Dbal;

use Doctrine\Common\Cache\Cache;
use Doctrine\DBAL\Connection;
use Rollerworks\Component\Search\SearchCondition;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class DoctrineDbalFactory
{
    /**
     * @var Cache
     */
    protected $cacheDriver;

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
     * @param Connection      $connection      Doctrine DBAL Connection object
     * @param SearchCondition $searchCondition SearchCondition object
     *
     * @return WhereBuilder
     */
    public function createWhereBuilder(Connection $connection, SearchCondition $searchCondition)
    {
        $whereBuilder = new WhereBuilder($connection, $searchCondition);

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
     * @param WhereBuilderInterface $whereBuilder
     * @param int                   $lifetime
     *
     * @throws \RuntimeException when no cache-driver is configured
     *
     * @return CacheWhereBuilder
     */
    public function createCacheWhereBuilder(WhereBuilderInterface $whereBuilder, int $lifetime = 0)
    {
        if (null === $this->cacheDriver) {
            throw new \RuntimeException('Unable to create CacheWhereBuilder, no CacheDriver is configured.');
        }

        return new CacheWhereBuilder($whereBuilder, $this->cacheDriver, $lifetime);
    }
}
