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

namespace Rollerworks\Component\Search\ApiPlatform\Processor;

use Psr\SimpleCache\CacheInterface as Cache;
use Rollerworks\Component\Search\Exception\UnexpectedTypeException;
use Rollerworks\Component\Search\Loader\ConditionExporterLoader;
use Rollerworks\Component\Search\Loader\InputProcessorLoader;
use Rollerworks\Component\Search\Processor\ProcessorConfig;
use Rollerworks\Component\Search\Processor\SearchPayload;
use Rollerworks\Component\Search\Processor\SearchProcessor;
use Rollerworks\Component\Search\SearchFactory;
use Symfony\Component\HttpFoundation\Request;

/**
 * Psr7SearchProcessor handles a search provided with a PSR-7 ServerRequest.
 *
 * SearchProcessor processes search-data.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class ApiSearchProcessor implements SearchProcessor
{
    private $searchFactory;
    private $inputFactory;
    private $exportFactory;
    private $cache;

    public function __construct(SearchFactory $searchFactory, InputProcessorLoader $inputFactory, ConditionExporterLoader $exportFactory, Cache $cache = null)
    {
        $this->searchFactory = $searchFactory;
        $this->inputFactory = $inputFactory;
        $this->exportFactory = $exportFactory;
        $this->cache = $cache ?? new NullCache();
    }

    /**
     * Process the request for a search operation.
     *
     * @param Request         $request The HttpFoundation Request to extract information from
     * @param ProcessorConfig $config  Input processor configuration
     *
     * @return SearchPayload The SearchPayload contains READ-ONLY information about
     *                       the processing
     */
    public function processRequest($request, ProcessorConfig $config): SearchPayload
    {
        if (!$request instanceof Request) {
            throw new UnexpectedTypeException($request, Request::class);
        }

        $input = $request->query->get('search', []);
        $payload = $this->processInput($config, $input);

        if (!$payload->changed && !empty($input) && $input !== $payload->exportedCondition) {
            $payload->changed = true;
        }

        return $payload;
    }

    private function processInput(ProcessorConfig $config, $input): SearchPayload
    {
        $payload = new SearchPayload();
        $payload->searchCode = '';

        if (!is_array($input) || [] === $input) {
            return $payload;
        }

        if (null !== $cachedPayload = $this->fetchCached($config, $input)) {
            return $cachedPayload;
        }

        $payload->searchCondition = $this->inputFactory->get('array')->process($config, $input);
        $this->searchFactory->optimizeCondition($payload->searchCondition);

        $this->exportCondition($payload);
        $this->storeCache($config, $payload);

        return $payload;
    }

    private function fetchCached(ProcessorConfig $config, $input): ?SearchPayload
    {
        $cacheKey = $this->getConditionCacheKey($config, json_encode($input));

        if (null !== $payload = $this->cache->get($cacheKey)) {
            try {
                $payload->searchCondition = $this->searchFactory->getSerializer()->unserialize($payload->searchCondition);
            } catch (\Exception $e) {
                $this->cache->delete($cacheKey);
                $payload = null;
            }
        }

        return $payload;
    }

    private function exportCondition(SearchPayload $payload)
    {
        if (null === $payload->searchCondition) {
            return;
        }

        $exported = $this->exportFactory->get('array')->exportCondition($payload->searchCondition);
        $payload->searchCode = http_build_query($exported);
        $payload->exportedCondition = $exported;
        $payload->exportedFormat = 'array';
    }

    private function storeCache(ProcessorConfig $config, SearchPayload $payload)
    {
        if ([] === $payload->exportedCondition) {
            return;
        }

        // Store the SearchCondition in the custom serialized format to allow deserialization later-on.
        $payload = clone $payload;
        $payload->changed = false;
        $payload->searchCondition = $this->searchFactory->getSerializer()->serialize($payload->searchCondition);

        $this->cache->set(
            $this->getConditionCacheKey($config, json_encode($payload->exportedCondition)),
            $payload,
            $config->getCacheTTL()
        );
    }

    private function getConditionCacheKey(ProcessorConfig $config, string $searchCode): string
    {
        return hash('sha256', $config->getFieldSet()->getSetName().'~'.$searchCode);
    }
}
