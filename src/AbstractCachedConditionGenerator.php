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

namespace Rollerworks\Component\Search\Doctrine\Orm;

use Psr\SimpleCache\CacheInterface as Cache;
use Rollerworks\Component\Search\Exception\BadMethodCallException;

/**
 * Handles caching of a Doctrine ORM ConditionGenerator.
 *
 * This checks if there is a cached result, if not it delegates
 * the creating to the parent and caches the result.
 *
 * Instead of calling getWhereClause()/updateQuery() on the ConditionGenerator
 * class you should call getWhereClause()/updateQuery() on this class instead.
 *
 * @internal this class should not be relied upon, use the ConditionGenerator
 *           interface instead for type hinting
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
abstract class AbstractCachedConditionGenerator implements ConditionGenerator
{
    /**
     * @var Cache
     */
    protected $cacheDriver;

    /**
     * @var null|int|\DateInterval
     */
    protected $ttl;

    /**
     * @var ConditionGenerator
     */
    protected $conditionGenerator;

    /**
     * @var string
     */
    protected $cacheKey;

    /**
     * @var string
     */
    protected $whereClause;

    /**
     * Constructor.
     *
     * @param AbstractConditionGenerator $conditionGenerator The actual ConditionGenerator
     * @param Cache                      $cacheDriver        PSR-16 SimpleCache instance. Use a custom pool to ease
     *                                                       purging invalidated items
     * @param null|int|\DateInterval     $ttl                Optional. The TTL value of this item. If no value is sent and
     *                                                       the driver supports TTL then the library may set a default value
     *                                                       for it or let the driver take care of that.
     */
    public function __construct(AbstractConditionGenerator $conditionGenerator, Cache $cacheDriver, int $ttl = null)
    {
        $this->cacheDriver = $cacheDriver;
        $this->ttl = $ttl;
    }

    /**
     * Set the default entity mapping configuration, only for fields
     * configured *after* this method.
     *
     * Note: Calling this method after calling setField() will not affect
     * fields that were already configured. Which means you can use this
     * method to configure chunks of configuration.
     *
     * @param string $entity Entity name (FQCN or Doctrine aliased)
     * @param string $alias  Table alias as used in the query "u" for `FROM Acme:Users AS u`
     *
     * @return $this
     */
    public function setDefaultEntity(string $entity, string $alias)
    {
        $this->guardNotGenerated();
        $this->conditionGenerator->setDefaultEntity($entity, $alias);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setField(string $fieldName, string $property, string $alias = null, string $entity = null, string $dbType = null)
    {
        $this->guardNotGenerated();
        $this->conditionGenerator->setField($fieldName, $property, $alias, $entity, $dbType);

        return $this;
    }

    /**
     * @throws BadMethodCallException When the where-clause is already generated
     */
    protected function guardNotGenerated()
    {
        if (null !== $this->whereClause) {
            throw new BadMethodCallException(
                'ConditionGenerator configuration methods cannot be accessed anymore once the where-clause is generated.'
            );
        }
    }
}
