<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Rollerworks\Component\Search\ApiPlatform\EventListener\InvalidSearchConditionExceptionListener;
use Rollerworks\Component\Search\ApiPlatform\EventListener\SearchConditionListener;
use Rollerworks\Component\Search\ApiPlatform\Metadata\DefaultConfigurationMetadataFactory;
use Rollerworks\Component\Search\ApiPlatform\Serializer\InvalidSearchConditionNormalizer;
use Symfony\Component\Cache\Psr16Cache;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $parameters = $container->parameters();
    $parameters->set('api_platform.validator.serialize_payload_fields', []);

    $services->set('rollerworks_search.api_platform.event_listener.condition', SearchConditionListener::class)
        ->args([
            service('rollerworks_search.factory'),
            service('rollerworks_search.input_loader'),
            service('api_platform.metadata.resource.metadata_collection_factory'),
            service('event_dispatcher'),
            inline_service(Psr16Cache::class)
                ->args([service('rollerworks.search_processor.cache')]),
        ])

        // kernel.request priority must be < 8 && > 4 to be executed after the Firewall but before ReadListener
        ->tag('kernel.event_listener', ['event' => 'kernel.request', 'method' => 'onKernelRequest', 'priority' => 5]);

    // InvalidSearchConditionException handling

    $services->set('rollerworks_search.api_platform.hydra.normalizer.invalid_search_condition', InvalidSearchConditionNormalizer::class)
        ->args([
            '%api_platform.validator.serialize_payload_fields%',
            service('api_platform.name_converter')->ignoreOnInvalid(),
        ])
        ->tag('serializer.normalizer', ['priority' => 64]);

    $services->set('rollerworks_search.api_platform.listener.invalid_search_condition', InvalidSearchConditionExceptionListener::class)
        ->args([
            service('api_platform.serializer'),
            '%api_platform.error_formats%',
        ])
        ->tag('kernel.event_listener', ['event' => 'kernel.exception', 'method' => 'onKernelException']);

    // MetadataFactory, execute after everything else but before caching.
    $services->set('rollerworks_search.api_platform.metadata.resource.default_context_factory', DefaultConfigurationMetadataFactory::class)
        ->decorate('api_platform.metadata.resource.metadata_collection_factory', null, -9)
        ->args([service('rollerworks_search.api_platform.metadata.resource.default_context_factory.inner')]);
};
