<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('rollerworks_search.doctrine_dbal.factory', \Rollerworks\Component\Search\Doctrine\Dbal\DoctrineDbalFactory::class)
        ->public()
        ->args([service('rollerworks_search.doctrine.cache')->nullOnInvalid()]);

    $services->set(\Rollerworks\Component\Search\Extension\Doctrine\Dbal\Type\FieldTypeExtension::class)
        ->tag('rollerworks_search.type_extension', ['extended_type' => \Rollerworks\Component\Search\Extension\Core\Type\SearchFieldType::class]);

    $services->set(\Rollerworks\Component\Search\Extension\Doctrine\Dbal\Type\BirthdayTypeExtension::class)
        ->args([inline_service(\Rollerworks\Component\Search\Extension\Doctrine\Dbal\Conversion\AgeDateConversion::class)])
        ->tag('rollerworks_search.type_extension', ['extended_type' => \Rollerworks\Component\Search\Extension\Core\Type\BirthdayType::class]);

    $services->set(\Rollerworks\Component\Search\Extension\Doctrine\Dbal\Type\MoneyTypeExtension::class)
        ->args([inline_service(\Rollerworks\Component\Search\Extension\Doctrine\Dbal\Conversion\MoneyValueConversion::class)])
        ->tag('rollerworks_search.type_extension', ['extended_type' => \Rollerworks\Component\Search\Extension\Core\Type\MoneyType::class]);
};
