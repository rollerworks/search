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

namespace Rollerworks\Bundle\SearchBundle\Processor;

use Psr\Http\Message\ServerRequestInterface as ServerRequest;
use Rollerworks\Component\Search\Exception\UnexpectedTypeException;
use Rollerworks\Component\Search\Processor\ProcessorConfig;
use Rollerworks\Component\Search\Processor\SearchPayload;
use Rollerworks\Component\Search\Processor\SearchProcessor;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Component\HttpFoundation\Request;

/**
 * HttpFoundationSearchProcessor handles a search provided with
 * eg. a PSR-7 ServerRequest or a HttpFoundation Request.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class HttpFoundationSearchProcessor implements SearchProcessor
{
    private $processor;

    /**
     * Constructor.
     *
     * @param SearchProcessor $processor
     */
    public function __construct(SearchProcessor $processor)
    {
        $this->processor = $processor;
    }

    /**
     * Process the request for a search operation.
     *
     * A processor is expected to follow a few simple conventions:
     *
     * * The processor must return a SearchPayload with the results of the processing.
     * * A new condition must mark the payload as changed.
     * * The SearchPayload#searchCode property is expected to contain an input processable
     *   search-condition (like JSON) which can be used safely within an URI, when the condition is valid.
     * * The client may provide the input format using the `format` query/parsedBody information, but the
     *   processor's implementation can choose to ignore this.
     *
     * A Processor must first check if a new condition is provided and fall-back
     * to the `searchCode` as active condition.
     *
     * @param ServerRequest|Request $request The ServerRequest to extract information from
     * @param ProcessorConfig       $config  Input processor configuration
     *
     * @return SearchPayload The SearchPayload contains READ-ONLY information about
     *                       the processing, and 'when there were no errors' the SearchCondition
     */
    public function processRequest($request, ProcessorConfig $config): SearchPayload
    {
        if ($request instanceof ServerRequest) {
            return $this->processor->processRequest($request, $config);
        }

        if (!$request instanceof Request) {
            throw new UnexpectedTypeException($request, [ServerRequest::class, Request::class]);
        }

        /** @var ServerRequest $request */
        $request = (new DiactorosFactory())->createRequest($request);

        return $this->processor->processRequest($request, $config);
    }
}
