<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Rollerworks\Component\Search\Exporter\StringQueryExporter;
use Rollerworks\Component\Search\Loader\ConditionExporterLoader;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('rollerworks_search.exporter_loader', ConditionExporterLoader::class)
        ->args([
            null,
            [], // All services with tag "rollerworks_search.condition_exporter" are inserted here by ExporterPass
        ]);

    $services->set('rollerworks_search.exporter.string_query', StringQueryExporter::class)
        ->tag('rollerworks_search.condition_exporter', ['format' => 'string_query']);

    $services->set('rollerworks_search.exporter.norm_string_query', \Rollerworks\Component\Search\Exporter\NormStringQueryExporter::class)
        ->tag('rollerworks_search.condition_exporter', ['format' => 'norm_string_query']);

    $services->set('rollerworks_search.condition_exporter.json', \Rollerworks\Component\Search\Exporter\JsonExporter::class)
        ->tag('rollerworks_search.condition_exporter', ['format' => 'json']);
};
