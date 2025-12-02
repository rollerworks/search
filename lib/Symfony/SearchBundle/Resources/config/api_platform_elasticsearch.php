<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Rollerworks\Component\Search\ApiPlatform\Elasticsearch\Extension\SearchExtension;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('rollerworks_search.api_platform.elasticsearch.query_extension.search', SearchExtension::class)
        ->private()
        ->args([
            service('request_stack'),
            service('doctrine'),
            service('rollerworks_search.elasticsearch.factory'),
            service('rollerworks_search.elasticsearch.client'),
        ])
        ->tag('api_platform.doctrine.orm.query_extension.collection', ['priority' => 32]);
};
