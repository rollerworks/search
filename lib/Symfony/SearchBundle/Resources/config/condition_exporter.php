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

use Rollerworks\Component\Search\Exporter\JsonExporter;
use Rollerworks\Component\Search\Exporter\NormStringQueryExporter;
use Rollerworks\Component\Search\Exporter\StringQueryExporter;
use Rollerworks\Component\Search\Loader\ConditionExporterLoader;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('rollerworks_search.exporter_loader', ConditionExporterLoader::class)
        ->args([
            null,
            [], // All services with tag "rollerworks_search.condition_exporter" are inserted here by ExporterPass
        ])
    ;

    $services->set('rollerworks_search.exporter.string_query', StringQueryExporter::class)
        ->tag('rollerworks_search.condition_exporter', ['format' => 'string_query'])
    ;

    $services->set('rollerworks_search.exporter.norm_string_query', NormStringQueryExporter::class)
        ->tag('rollerworks_search.condition_exporter', ['format' => 'norm_string_query'])
    ;

    $services->set('rollerworks_search.condition_exporter.json', JsonExporter::class)
        ->tag('rollerworks_search.condition_exporter', ['format' => 'json'])
    ;
};
