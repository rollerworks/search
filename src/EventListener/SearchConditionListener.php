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
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface as ResourceMetadataFactory;
use Rollerworks\Component\Search\Processor\ProcessorConfig;
use Rollerworks\Component\Search\Processor\SearchProcessor;
use Rollerworks\Component\Search\SearchFactory;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * SearchConditionListener handles search conditions provided with the Request query.
 *
 * The condition is expected to be provided as an ArrayInput format at `search`.
 * After this the Request attribute `_api_search_condition` is set with SearchCondition object.
 *
 * When there is an error the processor is expected to throw an exception.
 * Exceptions are not handled by this listener!
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class SearchConditionListener
{
    private $searchFactory;
    private $searchProcessor;
    private $urlGenerator;
    private $resourceMetadataFactory;

    /**
     * Constructor.
     *
     * @param SearchFactory           $searchFactory
     * @param SearchProcessor         $searchProcessor         ApiSearchProcessor instance
     * @param UrlGeneratorInterface   $urlGenerator
     * @param ResourceMetadataFactory $resourceMetadataFactory
     */
    public function __construct(SearchFactory $searchFactory, SearchProcessor $searchProcessor, UrlGeneratorInterface $urlGenerator, ResourceMetadataFactory $resourceMetadataFactory)
    {
        $this->searchFactory = $searchFactory;
        $this->searchProcessor = $searchProcessor;
        $this->urlGenerator = $urlGenerator;
        $this->resourceMetadataFactory = $resourceMetadataFactory;
    }

    /**
     * Listener callback.
     *
     * This listener is expected to be run after the Api EntryPoint but before ReadListener
     * on the kernel.request event.
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event): void
    {
        $request = $event->getRequest();

        if (!$request->isMethodCacheable() || !$request->attributes->has('_api_resource_class')) {
            return;
        }

        $resourceClass = $request->attributes->get('_api_resource_class');
        $searchConfig = $this->resourceMetadataFactory->create($resourceClass)->getAttribute('rollerworks_search');

        if (empty($searchConfig)) {
            return;
        }

        $searchConfig = $this->resolveSearchConfiguration($searchConfig, $resourceClass, $request);

        $config = new ProcessorConfig($this->searchFactory->createFieldSet($searchConfig['fieldset']), 'array');
        $this->configureProcessor($config, $searchConfig, $resourceClass);
        $payload = $this->searchProcessor->processRequest($request, $config);

        if ($payload->isValid() && $payload->isChanged()) {
            $routeArguments = array_merge(
                $request->query->all(),
                $this->resolveRouteArguments($request), // Execute after query to prevent overwriting.
                ['search' => $payload->exportedCondition] // Use array instead or string.
            );

            $event->setResponse(new RedirectResponse(
                $this->urlGenerator->generate($request->attributes->get('_route'), $routeArguments)
            ));

            return;
        }

        $request->attributes->set('_api_search_condition', $payload->searchCondition);
    }

    private function resolveSearchConfiguration(array $searchConfig, string $resourceClass, Request $request): array
    {
        if (empty($searchConfig['contexts'])) {
            throw new RuntimeException(
                sprintf(
                    'Resource "%s" is missing a contexts array. Add a "contexts" array with at least one entry.',
                    $resourceClass.'#attributes[rollerworks_search]'
                )
            );
        }

        $context = $request->attributes->get('_api_search_context', '_any');

        if (!isset($searchConfig['contexts'][$context])) {
            throw new RuntimeException(
                sprintf(
                    'Search context "%s" is not supported for Resource "%s", supported: "%s".',
                    $context,
                    $resourceClass.'#attributes[rollerworks_search][contexts]',
                    implode('", "', array_keys($searchConfig['contexts']))
                )
            );
        }

        if (empty($searchConfig['contexts'][$context]) || empty($searchConfig['contexts'][$context]['fieldset'])) {
            throw new RuntimeException(
                sprintf(
                    'Search context "%s" is incorrectly configured for Resource "%s", missing a "fieldset" reference.',
                    $context,
                    $resourceClass.'#attributes[rollerworks_search]'
                )
            );
        }

        $request->attributes->set('_api_search_context', $context);
        $request->attributes->set('_api_search_config', $searchConfig['contexts'][$context]);

        return $searchConfig['contexts'][$context];
    }

    private function configureProcessor(ProcessorConfig $config, array $options, string $resourceClass): void
    {
        if (empty($options['processor'])) {
            return;
        }

        foreach ($options['processor'] as $option => $value) {
            $method = 'set'.ucfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $option))));

            if (!method_exists($config, $method)) {
                throw new RuntimeException(sprintf('Processor option "%s" is not supported for Resource "%s".', $option, $resourceClass));
            }

            if (ctype_digit($value)) {
                $value = (int) $value;
            }

            $config->{$method}($value);
        }
    }

    private function resolveRouteArguments(Request $request): array
    {
        $values = [];

        foreach ($request->attributes->get('_route_params', []) as $name => $value) {
            if (('_locale' !== $name && '_format' !== $name && '_' === $name[0]) || is_array($value)) {
                continue;
            }

            $values[$name] = $value;
        }

        return $values;
    }
}
