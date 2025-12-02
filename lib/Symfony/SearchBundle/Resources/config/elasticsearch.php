<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Rollerworks\Component\Search\Elasticsearch\ElasticsearchFactory;
use Rollerworks\Component\Search\Elasticsearch\Extension\Conversion\CurrencyConversion;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('rollerworks_search.elasticsearch.factory', ElasticsearchFactory::class)
        ->public()
        ->args([
            service('rollerworks_search.elasticsearch.cache')->nullOnInvalid(),
            service('rollerworks_search.parameter_bag')->nullOnInvalid(),
        ]);

    $services->set('rollerworks_search.elasticsearch.client')
        ->synthetic();

    $services->set(CurrencyConversion::class);

    $services->set(\Rollerworks\Component\Search\Elasticsearch\Extension\Conversion\DateConversion::class);

    $services->set(\Rollerworks\Component\Search\Elasticsearch\Extension\Conversion\DateTimeConversion::class);

    $services->set(\Rollerworks\Component\Search\Elasticsearch\Extension\Type\FieldTypeExtension::class)
        ->tag('rollerworks_search.type_extension', ['extended_type' => \Rollerworks\Component\Search\Extension\Core\Type\SearchFieldType::class]);

    $services->set(\Rollerworks\Component\Search\Elasticsearch\Extension\Type\BirthdayTypeExtension::class)
        ->args([service(\Rollerworks\Component\Search\Elasticsearch\Extension\Conversion\DateConversion::class)])
        ->tag('rollerworks_search.type_extension', ['extended_type' => \Rollerworks\Component\Search\Extension\Core\Type\BirthdayType::class]);

    $services->set(\Rollerworks\Component\Search\Elasticsearch\Extension\Type\DateTypeExtension::class)
        ->args([service(\Rollerworks\Component\Search\Elasticsearch\Extension\Conversion\DateConversion::class)])
        ->tag('rollerworks_search.type_extension', ['extended_type' => \Rollerworks\Component\Search\Extension\Core\Type\DateType::class]);

    $services->set(\Rollerworks\Component\Search\Elasticsearch\Extension\Type\DateTimeTypeExtension::class)
        ->args([service(\Rollerworks\Component\Search\Elasticsearch\Extension\Conversion\DateTimeConversion::class)])
        ->tag('rollerworks_search.type_extension', ['extended_type' => \Rollerworks\Component\Search\Extension\Core\Type\DateTimeType::class]);

    $services->set(\Rollerworks\Component\Search\Elasticsearch\Extension\Type\MoneyTypeExtension::class)
        ->args([service(CurrencyConversion::class)])
        ->tag('rollerworks_search.type_extension', ['extended_type' => \Rollerworks\Component\Search\Extension\Core\Type\MoneyType::class]);
};
