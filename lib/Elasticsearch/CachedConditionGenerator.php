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

namespace Rollerworks\Component\Search\Elasticsearch;

use Psr\SimpleCache\CacheInterface as Cache;
use Rollerworks\Component\Search\SearchCondition;

/**
 * Class CachedConditionGenerator.
 */
class CachedConditionGenerator implements ConditionGenerator
{
    /**
     * @var ConditionGenerator
     */
    private $conditionGenerator;

    /**
     * @var Cache
     */
    private $cacheDriver;

    /**
     * @var string
     */
    private $cacheKey;

    /**
     * @var null|int|\DateInterval
     */
    private $cacheTtl;

    /**
     * @var null|array
     */
    private $query;

    /**
     * Constructor.
     *
     * @param ConditionGenerator     $conditionGenerator The actual ConditionGenerator to use when no cache exists
     * @param Cache                  $cacheDriver        PSR-16 SimpleCache instance. Use a custom pool to ease
     *                                                   purging invalidated items
     * @param null|int|\DateInterval $ttl                Optional. The TTL value of this item. If no value is sent and
     *                                                   the driver supports TTL then the library may set a default value
     *                                                   for it or let the driver take care of that.
     */
    public function __construct(ConditionGenerator $conditionGenerator, Cache $cacheDriver, $ttl = 0)
    {
        $this->conditionGenerator = $conditionGenerator;
        $this->cacheDriver = $cacheDriver;
        $this->cacheTtl = $ttl;
    }

    /**
     * {@inheritdoc}
     */
    public function registerField(string $fieldName, string $mapping)
    {
        $this->conditionGenerator->registerField($fieldName, $mapping);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getQuery(): ?array
    {
        if (null === $this->query) {
            $cacheKey = $this->getCacheKey('query');
            if ($this->cacheDriver->has($cacheKey)) {
                $this->query = $this->cacheDriver->get($cacheKey);
            } else {
                $this->query = $this->conditionGenerator->getQuery();
                $this->cacheDriver->set($cacheKey, $this->query, $this->cacheTtl);
            }
        }

        return $this->query;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function getMappings(): array
    {
        return $this->conditionGenerator->getMappings();
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchCondition(): SearchCondition
    {
        /** @noinspection PhpInternalEntityUsedInspection */
        return $this->conditionGenerator->getSearchCondition();
    }

    /**
     * @param string $type "query" or "mappings"
     *
     * @return string
     */
    private function getCacheKey(string $type): string
    {
        if (null === $this->cacheKey) {
            $searchCondition = $this->getSearchCondition();
            $this->cacheKey = hash(
                'sha256',
                $searchCondition->getFieldSet()->getSetName().
                "\n".
                serialize($searchCondition->getValuesGroup()).
                "\n".
                serialize($this->conditionGenerator->getMappings()).
                "\n".
                $type
            );
        }

        return $this->cacheKey;
    }
}
