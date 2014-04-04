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
use Rollerworks\Component\Search\Exception\BadMethodCallException;
use Rollerworks\Component\Search\Exception\UnexpectedTypeException;
use Rollerworks\Component\Search\SearchConditionInterface;

/***
 * Handles abstracted caching of the Doctrine WhereBuilder.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
abstract class AbstractCacheWhereBuilder
{
    /**
     * @var Cache
     */
    protected $cacheDriver;

    /**
     * @var integer
     */
    protected $cacheLifeTime;

    /**
     * @var object
     */
    protected $whereBuilder;

    /**
     * @var string
     */
    protected $cacheKey;

    /**
     * @var string
     */
    protected $keySuffix;

    /**
     * @var string
     */
    protected $whereClause;

    /**
     * @var array
     */
    protected $parameters = array();

    /**
     * Set the cache key.
     *
     * This method also accepts a callback that can calculate the key for you.
     * The callback will receive wherebuilder.
     *
     * @param string   $key
     * @param callback $callback
     *
     * @return self
     *
     * @throws BadMethodCallException
     */
    public function setCacheKey($key = null, $callback = null)
    {
        if ((null === $key && null === $callback) || ($callback && !is_callable($callback))) {
            throw new BadMethodCallException('Either a key or legal callback must be given.');
        }

        if ($callback) {
            $key = call_user_func($callback, $this->whereBuilder);
        }

        $this->cacheKey = (string) $key;

        return $this;
    }


    /**
     * Set an extra suffix for the caching key.
     *
     * This allows to make the key more unique.
     * For example, you can set the key to calculate automatically,
     * and add this suffix to ensure there is no problem with different mapping.
     *
     * @param string $key
     */
    public function setCacheKeySuffix($key)
    {
        $this->keySuffix = $key;
    }

    /**
     * Returns the generated/cached where-clause.
     *
     * @return string
     */
    abstract public function getWhereClause();

    /**
     * @return object
     */
    public function getInnerWhereBuilder()
    {
        return $this->whereBuilder;
    }

    /**
     * Returns the parameters that where set during the generation process.
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @return SearchConditionInterface
     */
    public function getSearchCondition()
    {
        return $this->whereBuilder->getSearchCondition();
    }
}
