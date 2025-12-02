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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Rollerworks\Component\Search\Extension\LazyExtension;
use Rollerworks\Component\Search\Field\GenericResolvedFieldTypeFactory;
use Rollerworks\Component\Search\Field\GenericTypeRegistry;
use Rollerworks\Component\Search\GenericSearchFactory;
use Rollerworks\Component\Search\LazyFieldSetRegistry;
use Rollerworks\Component\Search\ParameterBag;
use Rollerworks\Component\Search\SearchConditionSerializer;
use Rollerworks\Component\Search\SearchFactory;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('rollerworks_search.resolved_type_factory', GenericResolvedFieldTypeFactory::class);

    $services->set('rollerworks_search.fieldset_registry', LazyFieldSetRegistry::class)
        ->args([
            null,
            [], // All services with tag "rollerworks_search.fieldset" are inserted here by FieldSetRegistryPass
        ]);

    $services->set('rollerworks_search.condition_serializer', SearchConditionSerializer::class)
        ->args([service('rollerworks_search.factory')]);

    $services->set('rollerworks_search.type_registry', GenericTypeRegistry::class)
        ->args([
            // We don't need to be able to add more extensions.
            //   * more types can be registered with the rollerworks_search.type tag
            //   * more type extensions can be registered with the rollerworks_search.type_extension tag
            [service('rollerworks_search.extension')],
            service('rollerworks_search.resolved_type_factory'),
        ]);

    $services->set('rollerworks_search.factory', GenericSearchFactory::class)
        ->public()
        ->args([
            service('rollerworks_search.type_registry'),
            service('rollerworks_search.fieldset_registry'),
        ]);

    $services->alias(SearchFactory::class, 'rollerworks_search.factory');

    $services->set('rollerworks_search.extension', LazyExtension::class)
        ->args([
            null, // All services with tag "rollerworks_search.type" are inserted here by ExtensionPass
            iterator([]), // All services with tag "rollerworks_search.type_extension" are inserted here by ExtensionPass
        ]);

    $services->set('rollerworks_search.cache.adapter.array', ArrayAdapter::class)
        ->args([
            0 // default lifetime
        ]);

    $services->set(ParameterBag::class);
    $services->alias('rollerworks_search.parameter_bag', ParameterBag::class);
};
