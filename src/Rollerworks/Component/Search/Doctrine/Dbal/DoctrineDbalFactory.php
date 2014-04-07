<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search\Doctrine\Dbal;

use Doctrine\Common\Cache\Cache;
use Doctrine\DBAL\Driver\Connection;
use Rollerworks\Component\Search\SearchConditionInterface;

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
     * @param Connection               $connection      Doctrine DBAL Connection object
     * @param SearchConditionInterface $searchCondition SearchCondition object
     *
     * @return WhereBuilder
     */
    public function createWhereBuilder(Connection $connection, SearchConditionInterface $searchCondition)
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
     * @param integer               $lifetime
     *
     * @return CacheWhereBuilder
     */
    public function createCacheWhereBuilder(WhereBuilderInterface $whereBuilder, $lifetime = 0)
    {
        return new CacheWhereBuilder($whereBuilder, $this->cacheDriver, $lifetime);
    }
}
