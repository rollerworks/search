<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('rollerworks_search.input_loader', \Rollerworks\Component\Search\Loader\InputProcessorLoader::class)
        ->args([
            null,
            [], // services with tag "rollerworks_search.input_processor" are inserted here by InputProcessorPass
        ]);

    $services->alias(\Rollerworks\Component\Search\Loader\InputProcessorLoader::class, 'rollerworks_search.input_loader');

    $services->set('rollerworks_search.input.abstract', \Rollerworks\Component\Search\Input\StringQueryInput::class)
        ->abstract()
        ->args([service('rollerworks_search.validator')->nullOnInvalid()]);

    $services->set('rollerworks_search.input.string_query', \Rollerworks\Component\Search\Input\StringQueryInput::class)
        ->parent('rollerworks_search.input.abstract')
        ->args([service('rollerworks_search.translator_based_alias_resolver')->nullOnInvalid()])
        ->tag('rollerworks_search.input_processor', ['format' => 'string_query']);

    $services->set('rollerworks_search.input.norm_string_query', \Rollerworks\Component\Search\Input\NormStringQueryInput::class)
        ->parent('rollerworks_search.input.abstract')
        ->tag('rollerworks_search.input_processor', ['format' => 'norm_string_query']);

    $services->set('rollerworks_search.input.json', \Rollerworks\Component\Search\Input\JsonInput::class)
        ->parent('rollerworks_search.input.abstract')
        ->tag('rollerworks_search.input_processor', ['format' => 'json']);
};
