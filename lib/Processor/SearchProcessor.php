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

/**
 * A SearchProcessor handles a search provided with a Request.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface SearchProcessor
{
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
     * Note: The $request argument needs to accept at least ServerRequest,
     * other formats are of the implementations choice. The interface actor
     * must be informed about accepted request formats.
     *
     * @param ServerRequest|mixed $request The Request (object) to extract information from
     * @param ProcessorConfig     $config  Input processor configuration
     *
     * @return SearchPayload The SearchPayload contains READ-ONLY information about
     *                       the processing, and 'when there were no errors' the SearchCondition
     */
    public function processRequest($request, ProcessorConfig $config): SearchPayload;
}
