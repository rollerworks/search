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

use Rollerworks\Component\Search\Doctrine\Dbal\DoctrineDbalFactory;
use Rollerworks\Component\Search\Extension\Core\Type\BirthdayType;
use Rollerworks\Component\Search\Extension\Core\Type\MoneyType;
use Rollerworks\Component\Search\Extension\Core\Type\SearchFieldType;
use Rollerworks\Component\Search\Extension\Doctrine\Dbal\Conversion\AgeDateConversion;
use Rollerworks\Component\Search\Extension\Doctrine\Dbal\Conversion\MoneyValueConversion;
use Rollerworks\Component\Search\Extension\Doctrine\Dbal\Type\BirthdayTypeExtension;
use Rollerworks\Component\Search\Extension\Doctrine\Dbal\Type\FieldTypeExtension;
use Rollerworks\Component\Search\Extension\Doctrine\Dbal\Type\MoneyTypeExtension;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('rollerworks_search.doctrine_dbal.factory', DoctrineDbalFactory::class)
        ->public()
        ->args([service('rollerworks_search.doctrine.cache')->nullOnInvalid()])
    ;

    $services->set(FieldTypeExtension::class)
        ->tag('rollerworks_search.type_extension', ['extended_type' => SearchFieldType::class])
    ;

    $services->set(BirthdayTypeExtension::class)
        ->args([inline_service(AgeDateConversion::class)])
        ->tag('rollerworks_search.type_extension', ['extended_type' => BirthdayType::class])
    ;

    $services->set(MoneyTypeExtension::class)
        ->args([inline_service(MoneyValueConversion::class)])
        ->tag('rollerworks_search.type_extension', ['extended_type' => MoneyType::class])
    ;
};
