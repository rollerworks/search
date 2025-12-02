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

use Rollerworks\Component\Search\Elasticsearch\ElasticsearchFactory;
use Rollerworks\Component\Search\Elasticsearch\Extension\Conversion\CurrencyConversion;
use Rollerworks\Component\Search\Elasticsearch\Extension\Conversion\DateConversion;
use Rollerworks\Component\Search\Elasticsearch\Extension\Conversion\DateTimeConversion;
use Rollerworks\Component\Search\Elasticsearch\Extension\Type\BirthdayTypeExtension;
use Rollerworks\Component\Search\Elasticsearch\Extension\Type\DateTimeTypeExtension;
use Rollerworks\Component\Search\Elasticsearch\Extension\Type\DateTypeExtension;
use Rollerworks\Component\Search\Elasticsearch\Extension\Type\FieldTypeExtension;
use Rollerworks\Component\Search\Elasticsearch\Extension\Type\MoneyTypeExtension;
use Rollerworks\Component\Search\Extension\Core\Type\BirthdayType;
use Rollerworks\Component\Search\Extension\Core\Type\DateTimeType;
use Rollerworks\Component\Search\Extension\Core\Type\DateType;
use Rollerworks\Component\Search\Extension\Core\Type\MoneyType;
use Rollerworks\Component\Search\Extension\Core\Type\SearchFieldType;

return static function (ContainerConfigurator $container): void {
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
    $services->set(DateConversion::class);
    $services->set(DateTimeConversion::class);

    $services->set(FieldTypeExtension::class)
        ->tag('rollerworks_search.type_extension', ['extended_type' => SearchFieldType::class]);

    $services->set(BirthdayTypeExtension::class)
        ->args([service(DateConversion::class)])
        ->tag('rollerworks_search.type_extension', ['extended_type' => BirthdayType::class]);

    $services->set(DateTypeExtension::class)
        ->args([service(DateConversion::class)])
        ->tag('rollerworks_search.type_extension', ['extended_type' => DateType::class]);

    $services->set(DateTimeTypeExtension::class)
        ->args([service(DateTimeConversion::class)])
        ->tag('rollerworks_search.type_extension', ['extended_type' => DateTimeType::class]);

    $services->set(MoneyTypeExtension::class)
        ->args([service(CurrencyConversion::class)])
        ->tag('rollerworks_search.type_extension', ['extended_type' => MoneyType::class]);
};
