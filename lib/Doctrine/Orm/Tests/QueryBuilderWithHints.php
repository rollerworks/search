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

namespace Rollerworks\Component\Search\Tests\Doctrine\Orm;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;

/** @internal */
class QueryBuilderWithHints extends QueryBuilder
{
    /**
     * Sets a query hint.
     *
     * @param string $name  the name of the hint
     * @param mixed  $value the value of the hint
     *
     * @return self
     */
    public function setHint($name, $value)
    {
        $this->_hints[$name] = $value;

        return $this;
    }

    /**
     * Gets the value of a query hint. If the hint name is not recognized, FALSE is returned.
     *
     * @param string $name the name of the hint
     *
     * @return mixed the value of the hint or FALSE, if the hint name is not recognized
     */
    public function getHint($name)
    {
        return $this->_hints[$name] ?? false;
    }

    /**
     * Check if the query has a hint.
     *
     * @param string $name The name of the hint
     *
     * @return bool False if the query does not have any hint
     */
    public function hasHint($name)
    {
        return isset($this->_hints[$name]);
    }

    /**
     * Return the key value map of query hints that are currently set.
     *
     * @return array
     */
    public function getHints()
    {
        return $this->_hints;
    }

    /**
     * The map of query hints.
     *
     * @var array
     */
    private $_hints = [];

    /**
     * Constructs a Query instance from the current specifications of the builder.
     *
     * <code>
     *     $qb = $em->createQueryBuilder()
     *         ->select('u')
     *         ->from('User', 'u');
     *     $q = $qb->getQuery();
     *     $results = $q->execute();
     * </code>
     *
     * @return Query
     */
    public function getQuery()
    {
        $parameters = clone $this->getParameters();
        $query = $this->getEntityManager()->createQuery($this->getDQL())
            ->setParameters($parameters)
            ->setFirstResult($this->getFirstResult())
            ->setMaxResults($this->getMaxResults());

        if ($this->lifetime) {
            $query->setLifetime($this->lifetime);
        }

        if ($this->cacheMode) {
            $query->setCacheMode($this->cacheMode);
        }

        if ($this->cacheable) {
            $query->setCacheable($this->cacheable);
        }

        if ($this->cacheRegion) {
            $query->setCacheRegion($this->cacheRegion);
        }

        if ($this->_hints) {
            foreach ($this->_hints as $name => $value) {
                $query->setHint($name, $value);
            }
        }

        return $query;
    }
}
