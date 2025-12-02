<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('rollerworks_search.resolved_type_factory', \Rollerworks\Component\Search\Field\GenericResolvedFieldTypeFactory::class);

    $services->set('rollerworks_search.fieldset_registry', \Rollerworks\Component\Search\LazyFieldSetRegistry::class)
        ->args([
            null,
            [], // All services with tag "rollerworks_search.fieldset" are inserted here by FieldSetRegistryPass
        ]);

    $services->set('rollerworks_search.condition_serializer', \Rollerworks\Component\Search\SearchConditionSerializer::class)
        ->args([service('rollerworks_search.factory')]);

    $services->set('rollerworks_search.type_registry', \Rollerworks\Component\Search\Field\GenericTypeRegistry::class)
        ->args([
            // We don't need to be able to add more extensions.
            //   * more types can be registered with the rollerworks_search.type tag
            //   * more type extensions can be registered with the rollerworks_search.type_extension tag
            [service('rollerworks_search.extension')],
            service('rollerworks_search.resolved_type_factory'),
        ]);

    $services->set('rollerworks_search.factory', \Rollerworks\Component\Search\GenericSearchFactory::class)
        ->public()
        ->args([
            service('rollerworks_search.type_registry'),
            service('rollerworks_search.fieldset_registry'),
        ]);

    $services->alias(\Rollerworks\Component\Search\SearchFactory::class, 'rollerworks_search.factory');

    $services->set('rollerworks_search.extension', \Rollerworks\Component\Search\Extension\LazyExtension::class)
        ->args([
            null, // All services with tag "rollerworks_search.type" are inserted here by ExtensionPass
            iterator([]), // All services with tag "rollerworks_search.type_extension" are inserted here by ExtensionPass
        ]);

    $services->set('rollerworks_search.cache.adapter.array', \Symfony\Component\Cache\Adapter\ArrayAdapter::class)
        ->args([0]); // default lifetime

    $services->set(\Rollerworks\Component\Search\ParameterBag::class);
    $services->alias('rollerworks_search.parameter_bag', \Rollerworks\Component\Search\ParameterBag::class);
};
