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

use Rollerworks\Bundle\SearchBundle\ExceptionParser;
use Rollerworks\Component\ExceptionParser\ExceptionParserManager;
use Rollerworks\Component\Search\Exception\InvalidSearchConditionException;
use Rollerworks\Component\Search\Extension\Symfony\DependencyInjection\ExporterFactory;
use Rollerworks\Component\Search\Extension\Symfony\DependencyInjection\InputFactory;
use Rollerworks\Component\Search\Extension\Symfony\Validator\Validator;
use Rollerworks\Component\Search\Input\ProcessorConfig;
use Rollerworks\Component\Search\SearchConditionOptimizerInterface;
use Rollerworks\Component\Search\ValuesError;
use Rollerworks\Component\UriEncoder\UriEncoderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * SearchProcessorInterface processes search-data.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class SearchProcessor extends AbstractSearchProcessor
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
     * @var UriEncoderInterface
     */
    private $uriEncoder;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var Validator
     */
    private $validator;

    /**
     * Constructor.
     *
     * @param InputFactory                      $inputFactory
     * @param ExporterFactory                   $exportFactory
     * @param SearchConditionOptimizerInterface $conditionOptimizer
     * @param Validator                         $validator
     * @param TranslatorInterface               $translator
     * @param UriEncoderInterface               $uirEncoder
     * @param ProcessorConfig                   $config
     * @param string                            $uriPrefix          URI-prefix, used when there are multiple
     *                                                              search forms on a page
     * @param string                            $formFieldPattern   Form-field pattern for getting the value of
     *                                                              a form field like 'rollerworks_search[%s]',
     *                                                              placeholder is replaced with eg. 'filter'
     *                                                              or 'format'
     */
    public function __construct(
        InputFactory $inputFactory,
        ExporterFactory $exportFactory,
        SearchConditionOptimizerInterface $conditionOptimizer,
        Validator $validator,
        TranslatorInterface $translator,
        UriEncoderInterface $uirEncoder,

        ProcessorConfig $config,
        $uriPrefix = '',
        $formFieldPattern = 'rollerworks_search[%s]'
    ) {
        $this->inputFactory = $inputFactory;
        $this->exportFactory = $exportFactory;
        $this->conditionOptimizer = $conditionOptimizer;
        $this->validator = $validator;
        $this->translator = $translator;
        $this->uriEncoder = $uirEncoder;

        if (false === strpos($formFieldPattern, '%s')) {
            throw new \InvalidArgumentException('Missing "%s" placeholder in $formFieldPattern parameter.');
        }

        $this->config = $config;
        $this->uriPrefix = (string) $uriPrefix;
        $this->formFieldPattern = $formFieldPattern;
    }

    /**
     * {@inheritdoc}
     */
    public function processRequest(Request $request)
    {
        $this->request = $request;
        $isPost = $request->isMethod('POST');

        $input = $this->getRequestParam('filter', '', !$isPost);
        $format = $this->getRequestParam('format', 'filter_query', !$isPost);

        if ('' === $input) {
            $this->searchCondition = null;
            $this->searchCode = '';
            $this->errors = [];

            return $this;
        }

        if (!$isPost) {
            $input = $this->uriEncoder->decodeUri($input);
        }

        $this->processInput($input, $format);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function exportSearchCondition($format)
    {
        if (!$this->isValid(false)) {
            return;
        }

        $export = $this->exportFactory->create($format);

        return $export->exportCondition($this->searchCondition, 'filter_query' === $format);
    }

    /**
     * Processes the input to a SearchCondition.
     *
     * @param string $input
     * @param string $format
     */
    private function processInput($input, $format)
    {
        if ('' === $input) {
            return;
        }

        $this->searchCode = '';
        $this->searchCondition = null;

        try {
            $this->searchCondition = $this->inputFactory->create($format)->process($this->config, $input);
        } catch (InvalidSearchConditionException $e) {
            $this->errors[] = $e;
        } catch (\Exception $e) {
            $exceptionParams = $this->getExceptionMessage($e);

            $this->errors[] = new ValuesError(
                '.',
                strtr($exceptionParams['message'], $exceptionParams['parameters']),
                $exceptionParams['message'],
                $exceptionParams['parameters'],
                null,
                $e
            );
        }

        if (null === $this->searchCondition) {
            return;
        }

        $this->conditionOptimizer->process($this->searchCondition);

        // Validate after optimizing to reduce the number of messages.
        // A duplicate invalid value would be validated twice.
        if (false === $this->validator->validate($this->searchCondition)) {
            $this->errors[] = new InvalidSearchConditionException($this->searchCondition);

            return;
        }

        $this->searchCode = $this->uriEncoder->encodeUri($this->exportSearchCondition('filter_query'));
    }

    /**
     * Transforms the Exception to a Message object.
     *
     * @param \Exception $exception
     *
     * @throws \Exception When exception can not be parsed
     *
     * @return array
     */
    private function getExceptionMessage(\Exception $exception)
    {
        $exceptionParser = new ExceptionParserManager();
        $exceptionParser->addExceptionParser(new ExceptionParser\QueryExceptionParser());
        $exceptionParser->addExceptionParser(new ExceptionParser\SearchFieldRequiredExceptionParser());
        $exceptionParser->addExceptionParser(new ExceptionParser\SearchGroupsOverflowExceptionParser());
        $exceptionParser->addExceptionParser(new ExceptionParser\SearchValuesOverflowExceptionParser());
        $exceptionParser->addExceptionParser(new ExceptionParser\SearchGroupsNestingExceptionParser());
        $exceptionParser->addExceptionParser(new ExceptionParser\SearchUnsupportedValueTypeExceptionParser());
        $exceptionParser->addExceptionParser(new ExceptionParser\SearchUnknownFieldExceptionParser());
        $exceptionParser->addExceptionParser(new ExceptionParser\SearchProcessingExceptionParser());

        $params = $exceptionParser->processException($exception);

        // No compatible parser re-throw for external catching
        if ([] === $params) {
            throw $exception;
        }

        $newParams = [];

        foreach ($params['parameters'] as $name => $value) {
            if (is_array($value)) {
                $value = implode(', ', array_map([$this, 'formatValue'], $value));
            } else {
                $value = $this->formatValue($value);
            }

            if (null === $name) {
                $newParams[] = $value;
            } else {
                $newParams['{{ '.$name.' }}'] = $value;
            }
        }

        $params['parameters'] = $newParams;

        return $params;
    }

    private function formatValue($value)
    {
        if (ctype_alpha($value)) {
            $value = $this->translator->trans($value);
        } elseif (ctype_punct($value)) {
            $value = '"'.$value.'"';
        }

        return $value;
    }
}
