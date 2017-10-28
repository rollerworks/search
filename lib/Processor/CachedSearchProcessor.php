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

namespace Rollerworks\Component\Search\Processor;

use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Psr\SimpleCache\CacheInterface as Cache;
use Rollerworks\Component\Search\Exception\UnexpectedTypeException;
use Rollerworks\Component\Search\SearchFactory;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface as PropertyAccessor;

/**
 * The CachedSearchProcessor caches the SearchPayload
 * of a successfully processed search operation.
 *
 * The data is stored in a PSR-16 (SimpleCache) storage,
 * and should not be stored in filesystem or long-term storage.
 *
 * If your configuration disallows deep nested structures
 * it may be a better to not cache the SearchPayload.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class CachedSearchProcessor extends AbstractSearchProcessor
{
    private $processor;
    private $cache;

    /**
     * Constructor.
     *
     * @param Cache            $cache
     * @param SearchProcessor  $processor
     * @param SearchFactory    $searchFactory
     * @param PropertyAccessor $propertyAccessor
     */
    public function __construct(Cache $cache, SearchProcessor $processor, SearchFactory $searchFactory, PropertyAccessor $propertyAccessor = null)
    {
        parent::__construct($searchFactory, $propertyAccessor);

        $this->cache = $cache;
        $this->processor = $processor;
    }

    /**
     * {@inheritdoc}
     */
    public function processRequest($request, ProcessorConfig $config): SearchPayload
    {
        if (!$request instanceof ServerRequest) {
            throw new UnexpectedTypeException($request, ServerRequest::class);
        }

        if (0 === strcasecmp($request->getMethod(), 'POST')) {
            $payload = $this->processor->processRequest($request, $config);
            $this->storeCache($config, $payload);

            return $payload;
        }

        $currentCode = $this->getRequestParam($request->getQueryParams(), $config, 'search', '');

        if (!$currentCode) {
            return new SearchPayload();
        }

        if (null === $payload = $this->loadCondition($config, $currentCode)) {
            $payload = $this->processor->processRequest($request, $config);
            $this->storeCache($config, $payload);
        }

        return $payload;
    }

    private function loadCondition(ProcessorConfig $config, string $searchCode): ?SearchPayload
    {
        $cacheKey = $this->getConditionCacheKey($config, $searchCode);

        /** @var SearchPayload $payload */
        if (null !== $payload = $this->cache->get($cacheKey)) {
            try {
                $payload->searchCondition = $this->searchFactory->getSerializer()->unserialize(
                    $payload->searchCondition
                );

                return $payload;
            } catch (\Exception $e) {
                $this->cache->delete($cacheKey);
            }
        }

        return null;
    }

    private function storeCache(ProcessorConfig $config, SearchPayload $payload)
    {
        if ('' === $payload->searchCode || !$payload->isValid()) {
            return;
        }

        // Store the SearchCondition in the custom serialized format to allow deserialization later-on.
        $payload = clone $payload;
        $payload->changed = false;
        $payload->searchCondition = $this->searchFactory->getSerializer()->serialize($payload->searchCondition);
        $this->cache->set(
            $this->getConditionCacheKey($config, $payload->searchCode),
            $payload,
            $config->getCacheTTL()
        );
    }

    private function getConditionCacheKey(ProcessorConfig $config, string $searchCode): string
    {
        return hash('sha256', $config->getFieldSet()->getSetName().'~'.$searchCode);
    }
}
