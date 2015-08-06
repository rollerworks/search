<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Bundle\SearchBundle\Processor;

use Doctrine\Common\Cache\Cache;
use Rollerworks\Component\Search\Extension\Symfony\DependencyInjection\ExporterFactory;
use Rollerworks\Component\Search\Input\ProcessorConfig;
use Rollerworks\Component\Search\SearchConditionSerializer;
use Symfony\Component\HttpFoundation\Request;

/**
 * CacheSearchProcessor caches processed request data
 * for better performance.
 *
 * Cached data only contains only input-to-searchCondition (after formatting)
 * and exported formats.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class CacheSearchProcessor extends AbstractSearchProcessor
{
    /**
     * @var Cache
     */
    private $cacheDriver;

    /**
     * @var SearchProcessorInterface
     */
    private $processor;

    /**
     * @var SearchConditionSerializer
     */
    private $conditionSerializer;

    /**
     * @var ExporterFactory
     */
    private $exportFactory;

    /**
     * Constructor.
     *
     * @param SearchProcessorInterface  $processor
     * @param SearchConditionSerializer $conditionSerializer
     * @param ExporterFactory           $exportFactory
     * @param Cache                     $cacheDriver
     * @param ProcessorConfig           $config
     * @param string                    $uriPrefix
     */
    public function __construct(
        SearchProcessorInterface $processor,
        SearchConditionSerializer $conditionSerializer,
        ExporterFactory $exportFactory,
        Cache $cacheDriver,
        ProcessorConfig $config,
        $uriPrefix = ''
    ) {
        $this->processor = $processor;
        $this->conditionSerializer = $conditionSerializer;

        $this->cacheDriver = $cacheDriver;
        $this->config = $config;
        $this->uriPrefix = $uriPrefix;
        $this->exportFactory = $exportFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function processRequest(Request $request)
    {
        $this->request = $request;
        $isPost = $request->isMethod('POST');

        $this->searchCode = $this->getRequestParam('filter', '');
        $this->searchCondition = null;
        $this->errors = [];

        if (!$isPost && '' === $this->searchCode) {
            return $this;
        }

        // A new search condition is provided remove the old cache.
        if ($isPost && $this->searchCode) {
            $this->clearCache();
        }

        if ($isPost || !$this->loadConditionFromCache()) {
            if (!$this->processor->processRequest($request)->isValid()) {
                $this->errors = $this->processor->getErrors();
            } else {
                $this->searchCondition = $this->processor->getSearchCondition();
                $this->searchCode = $this->processor->getSearchCode();

                $this->storeCondition();
            }
        }

        return $this;
    }

    /**
     * Clears the cache for the current condition.
     *
     * @return bool
     */
    public function clearCache()
    {
        if (!$this->searchCode) {
            return false;
        }

        $this->cacheDriver->delete($this->getConditionCacheKey());

        $fieldSetName = $this->config->getFieldSet()->getSetName();

        if (!is_array($exportedFormats = $this->cacheDriver->fetch('search_export.'.$this->searchCode.'.formats'))) {
            $exportedFormats = [];
        }

        foreach ($exportedFormats as $format => $v) {
            $this->cacheDriver->delete('search_export.'.$fieldSetName.'.'.$this->searchCode.'.'.$format);
        }

        return true;
    }

    /**
     * Returns the exported format of the SearchCondition.
     *
     * @param string $format
     *
     * @throws \RuntimeException When there is no SearchCondition or its invalid
     *
     * @return string|array Exported format
     */
    public function exportSearchCondition($format)
    {
        if (!$this->isValid(false)) {
            return;
        }

        $cacheKey = 'search_export.'.$this->searchCode.'.'.$format;

        if ($this->cacheDriver->contains($cacheKey)) {
            return $this->cacheDriver->fetch($cacheKey);
        }

        $export = $this->exportFactory->create($format);
        $exported = $export->exportCondition($this->searchCondition, 'filter_query' === $format);

        $this->storeExported($exported, $format);

        return $exported;
    }

    private function storeExported($exported, $format)
    {
        if (!is_array($exportedFormats = $this->cacheDriver->fetch('search_export.'.$this->searchCode.'.formats'))) {
            $exportedFormats = [];
        }

        $exportedFormats[$format] = true;

        $this->cacheDriver->save('search_export.'.$this->searchCode.'.'.$format, $exported);
        $this->cacheDriver->save('search_export.'.$this->searchCode.'.formats', $exportedFormats);
    }

    private function storeCondition()
    {
        if (!$this->isValid(false)) {
            return;
        }

        $this->cacheDriver->save(
            $this->getConditionCacheKey(),
            $this->conditionSerializer->serialize($this->searchCondition)
        );
    }

    private function loadConditionFromCache()
    {
        $cacheKey = $this->getConditionCacheKey();

        if ($this->cacheDriver->contains($cacheKey)) {
            try {
                $this->searchCondition = $this->conditionSerializer->unserialize($this->cacheDriver->fetch($cacheKey));

                return true;
            } catch (\Exception $e) {
                $this->clearCache();
            }
        }

        return false;
    }

    private function getConditionCacheKey()
    {
        return 'search_condition.'.$this->config->getFieldSet()->getSetName().'.'.$this->searchCode;
    }
}
