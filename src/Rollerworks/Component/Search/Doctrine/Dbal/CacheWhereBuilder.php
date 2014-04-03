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
use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\NativeQuery;
use Doctrine\ORM\Query as DqlQuery;
use Doctrine\ORM\QueryBuilder;
use Rollerworks\Component\Search\Exception\BadMethodCallException;
use Rollerworks\Component\Search\Exception\UnexpectedTypeException;
use Rollerworks\Component\Search\SearchConditionInterface;

/***
 * Handles caching of the Doctrine ORM WhereBuilder.
 *
 * Note. For best performance caching of the WhereClause should be done on a
 * per user-session fieldset basis. This ensures enough uniqueness and
 * no complex serialization.
 *
 * This checks if there is a cached result, if not it delegates
 * the creating to the parent and caches the result.
 *
 * Instead of calling getWhereClause() on the WhereBuilder class
 * you should call getWhereClause() on this class instead.
 *
 * WARNING. Any changes to the entities mapping should invalidate the cache
 * the system does not do this automatically.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class CacheWhereBuilder implements WhereBuilderInterface
{
    /**
     * @var Cache
     */
    private $cacheDriver;

    /**
     * @var integer
     */
    private $cacheLifeTime;

    /**
     * @var WhereBuilderInterface
     */
    private $whereBuilder;

    /**
     * @var string
     */
    private $cacheKey;

    /**
     * @var string
     */
    private $keySuffix;

    /**
     * @var string
     */
    private $whereClause;

    /**
     * @var array
     */
    private $parameters = array();

    /**
     * @var array
     */
    private $parameterTypes = array();

    /**
     * Constructor.
     *
     * @param WhereBuilderInterface $whereBuilder The WhereBuilder to use for generating and updating the query
     * @param Cache                 $cacheDriver  Doctrine Cache instance
     * @param integer               $lifeTime     Lifetime in seconds after which the cache is expired
     *
     * @throws UnexpectedTypeException when the whereBuilder is invalid
     */
    public function __construct(WhereBuilderInterface $whereBuilder, Cache $cacheDriver, $lifeTime = 0)
    {
        $this->cacheDriver = $cacheDriver;
        $this->cacheLifeTime = (int) $lifeTime;
        $this->whereBuilder = $whereBuilder;
    }

    /**
     * Set the cache key.
     *
     * This method also accepts a callback that can calculate the key for you.
     * The callback will receive both the query and search-condition in order.
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
            $key = call_user_func($callback, $this->whereBuilder->getQuery(), $this->whereBuilder->getSearchCondition());
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
     * @see WhereBuilder::getWhereClause()
     *
     * @param boolean $embedValues Whether to embed the values, default is to assign as parameters.
     *
     * @return string
     */
    public function getWhereClause($embedValues = false)
    {
        if ($this->whereClause) {
            return $this->whereClause;
        }

        $cacheKey = 'rw_search.doctrine.dba.where.'.$this->cacheKey.$this->keySuffix ? '_'.$this->keySuffix : '';

        if ($this->cacheDriver->contains($cacheKey)) {
            $data = $this->cacheDriver->fetch($cacheKey);

            $this->whereClause = $data[0];
            $this->parameters = $data[1];
            $this->parameterTypes = $data[1];

            $this->resolveParametersType();
        } else {
            $this->whereClause = $this->whereBuilder->getWhereClause($embedValues);
            $this->parameters = $this->whereBuilder->getParameterS();
            $this->parameterTypes = $this->whereBuilder->getParameterTypes();

            $this->cacheDriver->save($cacheKey, array($this->whereClause, $this->whereBuilder->getParameters(), $this->serializeParameterTypes($this->whereBuilder->getParameterTypes())), $this->cacheLifeTime);
        }

        return $this->whereClause;
    }

    /**
     * @return WhereBuilder
     */
    public function getInnerWhereBuilder()
    {
        return $this->whereBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * {@inheritdoc}
     */
    public function getParameterTypes()
    {
       return $this->parameterTypes;
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchCondition()
    {
        return $this->whereBuilder->getSearchCondition();
    }

    /**
     * {@inheritdoc}
     */
    public function bindParameters(Statement $statement)
    {
        if (!$this->whereClause) {
            throw new BadMethodCallException('No Parameters available, call getWhereClause() first.');
        }

        foreach ($this->parameters as $paramName => $paramValue) {
            $statement->bindValue($paramName, $paramValue, $this->parameterTypes[$paramName]);
        }
    }

    /**
     * @param Type[] $types
     *
     * @return string[]
     */
    private function serializeParameterTypes(array $types)
    {
        $typesArray = array();
        foreach ($types as $name => $type) {
            $typesArray[$name] = $type->getName();
        }

        return $typesArray;
    }

    private function resolveParametersType()
    {
        foreach ($this->parameterTypes as $name => $type) {
            $this->parameterTypes[$name] = Type::getType($type);
        }
    }
}
