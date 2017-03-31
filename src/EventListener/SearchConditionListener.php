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

namespace Rollerworks\Component\Search\ApiPlatform\EventListener;

use ApiPlatform\Core\Api\UrlGeneratorInterface;
use Rollerworks\Component\Search\Exception\InvalidSearchConditionException;
use Rollerworks\Component\Search\Processor\ProcessorConfig;
use Rollerworks\Component\Search\Processor\SearchProcessor;
use Rollerworks\Component\Search\SearchFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

final class SearchConditionListener
{
    private $searchFactory;
    private $searchProcessor;
    private $urlGenerator;

    public function __construct(SearchFactory $searchFactory, SearchProcessor $searchProcessor, UrlGeneratorInterface $urlGenerator)
    {
        $this->searchFactory = $searchFactory;
        $this->searchProcessor = $searchProcessor;
        $this->urlGenerator = $urlGenerator;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if ($request->isMethod(Request::METHOD_DELETE) || !$request->attributes->has('_api_respond')) {
            return;
        }

        // XXX Allow to configure a resolver (listener), to support admin/frontend (based on serialization context)
        // Else read from the ResourceMetadata
        // Provide config in the ResourceMetadata or `_api_search_config` (object)
        if (null === $fieldSet = $request->attributes->get('_api_search_fieldset')) {
            return;
        }

        // FIXME Make this configurable: array to method calls
        // XXX Try to guess format based on Accept

        $config = new ProcessorConfig($this->searchFactory->createFieldSet($fieldSet), 'json');
        $payload = $this->searchProcessor->processRequest($request, $config);

        if (!$payload->isValid()) {
            throw new InvalidSearchConditionException($payload->messages);
        }

        if ($payload->isValid() && $payload->isChanged()) {
            return new RedirectResponse(
                $this->urlGenerator->generate(
                    $request->attributes->get('_route'),
                    array_merge(
                        $request->query->all(),
                        ['search' => $payload->searchCode]
                    )
                )
            );
        }

        $request->attributes->set('_api_search_condition', $payload->searchCondition);
    }
}
