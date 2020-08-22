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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Type;
use Psr\SimpleCache\CacheInterface as Cache;

abstract class AbstractCachedConditionGenerator
{
    /**
     * @var Cache
     */
    protected $cacheDriver;

    /**
     * @var int|\DateInterval|null
     */
    protected $cacheLifeTime;

    /**
     * @var string|null
     */
    protected $cacheKey;

    /**
     * @var ArrayCollection
     */
    protected $parameters;

    /**
     * @param Cache                  $cacheDriver PSR-16 SimpleCache instance. Use a custom pool to ease
     *                                            purging invalidated items
     * @param int|\DateInterval|null $ttl         Optional. The TTL value of this item. If no value is sent and
     *                                            the driver supports TTL then the library may set a default
     *                                            value for it or let the driver take care of that.
     */
    protected function __construct(Cache $cacheDriver, $ttl = null)
    {
        $this->cacheDriver = $cacheDriver;
        $this->cacheLifeTime = $ttl;
        $this->parameters = new ArrayCollection();
    }

    protected function getFromCache(string $cacheKey): ?array
    {
        $cached = $this->cacheDriver->get($cacheKey);

        if (!\is_array($cached) || !isset($cached[0], $cached[1]) || !\is_string($cached[0]) || !\is_array($cached[1])) {
            return null;
        }

        try {
            $cached[1] = $this->unpackParameters($cached[1]);

            return $cached;
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function unpackParameters(array $provided): ArrayCollection
    {
        $parameters = new ArrayCollection();

        foreach ($provided as $name => [$value, $type]) {
            $parameters->set($name, [$value, $type === null ? null : Type::getType($type)]);
        }

        return $parameters;
    }

    protected function packParameters(ArrayCollection $provided): array
    {
        $parameters = [];

        foreach ($provided as $name => [$value, $type]) {
            $parameters[$name] = [$value, $type === null ? null : $type->getName()];
        }

        return $parameters;
    }

    public function getParameters(): ArrayCollection
    {
        return $this->parameters;
    }
}
