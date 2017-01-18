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
use Rollerworks\Component\Search\Extension\Symfony\DependencyInjection\InputFactory;
use Rollerworks\Component\Search\Extension\Symfony\Validator\Validator;
use Rollerworks\Component\Search\Input\ProcessorConfig;
use Rollerworks\Component\Search\SearchConditionOptimizerInterface;
use Rollerworks\Component\Search\SearchConditionSerializer;
use Rollerworks\Component\UriEncoder\UriEncoderInterface;
use Symfony\Component\Translation\TranslatorInterface;

class SearchProcessorFactory implements SearchProcessorFactoryInterface
{
    /**
     * @var InputFactory
     */
    private $inputFactory;

    /**
     * @var ExporterFactory
     */
    private $exportFactory;

    /**
     * @var SearchConditionOptimizerInterface
     */
    private $conditionOptimizer;

    /**
     * @var SearchConditionSerializer
     */
    private $conditionSerializer;

    /**
     * @var Validator
     */
    private $validator;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var UriEncoderInterface
     */
    private $uirEncoder;

    /**
     * @var Cache
     */
    private $cacheAdapter;

    /**
     * Constructor.
     *
     * @param InputFactory                      $inputFactory
     * @param ExporterFactory                   $exportFactory
     * @param SearchConditionOptimizerInterface $conditionOptimizer
     * @param SearchConditionSerializer         $conditionSerializer
     * @param Validator                         $validator
     * @param TranslatorInterface               $translator
     * @param UriEncoderInterface               $uirEncoder
     * @param Cache                             $cacheAdapter
     */
    public function __construct(
        InputFactory $inputFactory,
        ExporterFactory $exportFactory,
        SearchConditionOptimizerInterface $conditionOptimizer,
        SearchConditionSerializer $conditionSerializer,
        Validator $validator,
        TranslatorInterface $translator,
        UriEncoderInterface $uirEncoder,
        Cache $cacheAdapter
    ) {
        $this->inputFactory = $inputFactory;
        $this->exportFactory = $exportFactory;
        $this->conditionOptimizer = $conditionOptimizer;
        $this->conditionSerializer = $conditionSerializer;
        $this->validator = $validator;
        $this->translator = $translator;
        $this->uirEncoder = $uirEncoder;
        $this->cacheAdapter = $cacheAdapter;
    }

    /**
     * Creates a new SearchProcessor instance.
     *
     * @param ProcessorConfig $config           Input Processor configuration object
     * @param string          $uriPrefix        URL prefix to allow multiple processors per page
     * @param bool            $cached           Use cached processor (recommended paged results)
     * @param string          $formFieldPattern Form-field pattern for getting the value of
     *                                          a form field like 'rollerworks_search[%s]',
     *                                          placeholder is replaced with eg. 'filter'
     *                                          or 'format'
     *
     * @return SearchProcessor|CacheSearchProcessor
     */
    public function createProcessor(
        ProcessorConfig $config,
        $uriPrefix = '',
        $formFieldPattern = 'rollerworks_search[%s]',
        $cached = true
    ) {
        $processor = new SearchProcessor(
            $this->inputFactory,
            $this->exportFactory,
            $this->conditionOptimizer,
            $this->validator,
            $this->translator,
            $this->uirEncoder,
            $config,
            $uriPrefix,
            $formFieldPattern
        );

        if ($cached) {
            $processor = new CacheSearchProcessor(
                $processor,
                $this->conditionSerializer,
                $this->exportFactory,
                $this->cacheAdapter,
                $config,
                $uriPrefix
            );
        }

        return $processor;
    }
}
