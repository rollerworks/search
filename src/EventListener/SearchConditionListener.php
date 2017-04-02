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
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface as ResourceMetadataFactory;
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
    private $resourceMetadataFactory;

    public function __construct(SearchFactory $searchFactory, SearchProcessor $searchProcessor, UrlGeneratorInterface $urlGenerator, ResourceMetadataFactory $resourceMetadataFactory)
    {
        $this->searchFactory = $searchFactory;
        $this->searchProcessor = $searchProcessor;
        $this->urlGenerator = $urlGenerator;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if (!$request->isMethodCacheable() || !$request->attributes->has('_api_resource_class')) {
            return;
        }

        if (null === $fieldSet = $this->resolveFieldSetName($request)) {
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
            $routeArguments = array_merge(
                $request->query->all(),
                ['search' => $payload->exportedCondition] // Use array instead or string ()
            );

            if (null !== $format = $request->attributes->get('_format')) {
                $routeArguments['_format'] = $format;
            }

            $event->setResponse(new RedirectResponse(
                $this->urlGenerator->generate($request->attributes->get('_route'), $routeArguments)
            ));
        }

        $request->attributes->set('_api_search_condition', $payload->searchCondition);
    }

    private function resolveFieldSetName(Request $request): ?string
    {
        if (null !== $fieldSet = $request->attributes->get('_api_search_fieldset')) {
            return $fieldSet;
        }

        $resourceClass = $request->attributes->get('_api_resource_class');
        $searchConfig = $this->resourceMetadataFactory->create($resourceClass)->getAttribute('rollerworks_search');

        if (empty($searchConfig)) {
            return null;
        }

        if (!empty($searchConfig['fieldset'])) {
            return $searchConfig['fieldset'];
        }

        if (!empty($searchConfig['fields'])) {
            return $resourceClass;
        }

        return null;
    }
}
