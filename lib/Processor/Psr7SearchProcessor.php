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
use Rollerworks\Component\Search\ConditionErrorMessage;
use Rollerworks\Component\Search\Exception\InvalidSearchConditionException;
use Rollerworks\Component\Search\Exception\UnexpectedTypeException;
use Rollerworks\Component\Search\Loader\ConditionExporterLoader;
use Rollerworks\Component\Search\Loader\InputProcessorLoader;
use Rollerworks\Component\Search\SearchFactory;
use Rollerworks\Component\UriEncoder\Encoder\Base64UriEncoder;
use Rollerworks\Component\UriEncoder\Encoder\GZipCompressionDecorator;
use Rollerworks\Component\UriEncoder\UriEncoderInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface as PropertyAccessor;

/**
 * Psr7SearchProcessor handles a search provided with a PSR-7 ServerRequest.
 *
 * SearchProcessor processes search-data.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class Psr7SearchProcessor extends AbstractSearchProcessor
{
    private $uriEncoder;
    private $inputFactory;
    private $exportFactory;

    /**
     * Constructor.
     *
     * @param SearchFactory           $searchFactory
     * @param InputProcessorLoader    $inputFactory
     * @param ConditionExporterLoader $exportFactory
     * @param UriEncoderInterface     $uriEncoder
     * @param PropertyAccessor        $propertyAccessor
     */
    public function __construct(
        SearchFactory $searchFactory,
        InputProcessorLoader $inputFactory,
        ConditionExporterLoader $exportFactory,
        UriEncoderInterface $uriEncoder = null,
        PropertyAccessor $propertyAccessor = null
    ) {
        parent::__construct($searchFactory, $propertyAccessor);

        $this->uriEncoder = $uriEncoder ?? new GZipCompressionDecorator(new Base64UriEncoder());
        $this->inputFactory = $inputFactory;
        $this->exportFactory = $exportFactory;
    }

    /**
     * Process the request for a search operation.
     *
     * The input format must be provided
     *
     * @param ServerRequest   $request The ServerRequest to extract information from
     * @param ProcessorConfig $config  Input processor configuration
     *
     * @return SearchPayload The SearchPayload contains READ-ONLY information about
     *                       the processing
     */
    public function processRequest($request, ProcessorConfig $config): SearchPayload
    {
        if (!$request instanceof ServerRequest) {
            throw new UnexpectedTypeException($request, ServerRequest::class);
        }

        if (0 === strcasecmp($request->getMethod(), 'POST')) {
            if (!is_array($parameters = $request->getParsedBody())) {
                $parameters = [];
            }

            $format = $this->getRequestParam($parameters, $config, 'format', $config->getDefaultFormat());
            $input = $this->getRequestParam($parameters, $config, 'search');

            $payload = new SearchPayload(true);
            $this->processInput($payload, $config, $input, $format);
            $this->exportCondition($payload, $config, $format);

            return $payload;
        }

        $payload = new SearchPayload(false);
        $searchCode = $this->getRequestParam($request->getQueryParams(), $config, 'search', '');

        if ('' !== $searchCode) {
            $parts = explode('~', $searchCode, 2);
            $input = $this->uriEncoder->decodeUri($parts[0]);
            $format = $parts[1] ?? $config->getExportFormat();

            if (null === $input) {
                $payload->messages = [
                    ConditionErrorMessage::withMessageTemplate(
                        '',
                        'Invalid search code, check if the URL was truncated.'
                    ),
                ];

                return $payload;
            }

            $this->processInput($payload, $config, $input, 'json');
            $this->exportCondition($payload, $config, $format);
        }

        return $payload;
    }

    private function processInput(SearchPayload $payload, ProcessorConfig $config, $input, string $format)
    {
        $payload->searchCode = '';
        $payload->searchCondition = null;
        $payload->exportedCondition = null;
        $payload->exportedFormat = null;
        $payload->messages = [];

        if (null === $input || (is_string($input) && '' === trim($input)) || (is_array($input) && [] === $input)) {
            return;
        }

        try {
            $payload->searchCondition = $this->inputFactory->get($format)->process($config, $input);
            $this->searchFactory->optimizeCondition($payload->searchCondition);

            $payload->searchCode = $this->uriEncoder->encodeUri(
                $this->exportFactory->get('json')->exportCondition($payload->searchCondition)
            );
        } catch (InvalidSearchConditionException $e) {
            $payload->messages = $e->getErrors();
        }
    }

    private function exportCondition(SearchPayload $payload, ProcessorConfig $config, string $format = null)
    {
        if (null === $payload->searchCondition) {
            return;
        }

        $exportFormat = $format ?? $config->getDefaultFormat();
        $payload->searchCode .= '~'.$exportFormat;
        $payload->exportedFormat = $exportFormat;
        $payload->exportedCondition = $this->exportFactory->get($exportFormat)->exportCondition(
            $payload->searchCondition
        );
    }
}
