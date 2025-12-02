<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('rollerworks_search.api_platform.doctrine.orm.query_extension.search', \Rollerworks\Component\Search\ApiPlatform\Doctrine\Orm\Extension\SearchExtension::class)
        ->args([
            service('request_stack'),
            service('rollerworks_search.doctrine_orm.factory'),
        ])
        ->tag('api_platform.doctrine.orm.query_extension.collection', ['priority' => 32]);
};
