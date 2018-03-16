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
use Rollerworks\Component\Search\Processor\AbstractSearchProcessor;
use Rollerworks\Component\Search\Processor\ProcessorConfig;
use Rollerworks\Component\Search\Processor\SearchPayload;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\SearchFactory;
use Rollerworks\Component\Search\Value\ValuesGroup;
use Symfony\Component\HttpFoundation\Request;

/**
 * ApiSearchProcessor handles a search provided with a Symfony Request.
 *
 * The search-query needs to be provided as either a string (norm_string_query)
 * or as an array. The default search-format is silently ignored.
 *
 * Note: Unlike the Psr7SearchProcessor the exportedCondition must be used
 * for the URI, the search-code is used caching only and cannot be processed.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class ApiSearchProcessor extends AbstractSearchProcessor
{
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

        $input = $request->query->get('search', '');
        $format = is_array($input) ? 'array' : 'norm_string_query';
        $payload = $this->processInput($config, $input, $format);

        if (!$payload->changed && !empty($input) && $input !== $payload->exportedCondition) {
            $payload->changed = true;
        }

        return $payload;
    }

    private function processInput(ProcessorConfig $config, $input, string $format): SearchPayload
    {
        $payload = new SearchPayload();
        $payload->searchCode = '';

        if ([] === $input || (is_scalar($input) && '' === trim((string) $input))) {
            // Input is empty, but a SearchCondition should always be set to allow for a pre-condition.
            $payload->searchCondition = new SearchCondition($config->getFieldSet(), new ValuesGroup());

            return $payload;
        }

        $cacheKey = $this->getConditionCacheKey($config, $this->getSearchCode($input), $format);

        if (null !== $cachedPayload = $this->fetchCached($cacheKey)) {
            return $cachedPayload;
        }

        $payload->searchCondition = $this->inputFactory->get($format)->process($config, $input);
        $this->searchFactory->optimizeCondition($payload->searchCondition);

        $this->exportCondition($payload, $format);
        $this->storeCache($config, $payload);

        return $payload;
    }

    private function fetchCached(string $cacheKey): ?SearchPayload
    {
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

    private function exportCondition(SearchPayload $payload, string $format): void
    {
        if (null === $payload->searchCondition) {
            return;
        }

        $exported = $this->exportFactory->get($format)->exportCondition($payload->searchCondition);

        if (is_array($exported)) {
            // Parse query-string back into a array to by-pass bool conversion in URI
            // https://github.com/rollerworks/search/issues/177
            parse_str(http_build_query($exported), $exported);
        }

        $payload->exportedCondition = $exported;
        $payload->exportedFormat = $format;
        $payload->searchCode = $this->getSearchCode($exported);
    }

    private function storeCache(ProcessorConfig $config, SearchPayload $payload): void
    {
        if (null === $payload->searchCondition) {
            return;
        }

        // Store the SearchCondition in the custom serialized format to allow deserialization later-on.
        $payload = clone $payload;
        $payload->changed = false;
        $payload->searchCondition = $this->searchFactory->getSerializer()->serialize($payload->searchCondition);

        $this->cache->set(
            $this->getConditionCacheKey($config, $payload->searchCode, $payload->exportedFormat),
            $payload,
            $config->getCacheTTL()
        );
    }

    private function getConditionCacheKey(ProcessorConfig $config, string $searchCode, string $format): string
    {
        return hash('sha256', $config->getFieldSet()->getSetName().'~'.$searchCode.'~'.$format);
    }

    private function getSearchCode($input): string
    {
        if (is_scalar($input)) {
            return 'S:'.urlencode($input);
        }

        // Parse query-string back into a array to by-pass bool conversion in URI
        // https://github.com/rollerworks/search/issues/177
        parse_str(http_build_query($input), $input);

        return 'A:'.json_encode($input);
    }
}
