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
use Rollerworks\Component\Search\ParameterBag;
use Rollerworks\Component\Search\SearchCondition;

class ElasticsearchFactory
{
    /**
     * @var Cache|null
     */
    private $cacheDriver;

    /**
     * @var ParameterBag|null
     */
    private $parameterBag;

    public function __construct(?Cache $cacheDriver = null, ?ParameterBag $parameterBag = null)
    {
        $this->cacheDriver = $cacheDriver;
        $this->parameterBag = $parameterBag;
    }

    /**
     * Creates a new QueryConditionGenerator for the SearchCondition.
     *
     * Conversions are applied using the 'doctrine_dbal_conversion' option.
     *
     * @param SearchCondition $searchCondition SearchCondition
     */
    public function createConditionGenerator(SearchCondition $searchCondition): ConditionGenerator
    {
        return new QueryConditionGenerator($searchCondition, $this->parameterBag);
    }

    /**
     * Creates a new CachedConditionGenerator instance for the given ConditionGenerator.
     *
     * @param int|\DateInterval|null $ttl Optional. The TTL value of this item. If no value is sent and
     *                                    the driver supports TTL then the library may set a default value
     *                                    for it or let the driver take care of that.
     */
    public function createCachedConditionGenerator(ConditionGenerator $conditionGenerator, $ttl = 0): ConditionGenerator
    {
        if (null === $this->cacheDriver) {
            return $conditionGenerator;
        }

        return new CachedConditionGenerator($conditionGenerator, $this->cacheDriver, $ttl);
    }
}
