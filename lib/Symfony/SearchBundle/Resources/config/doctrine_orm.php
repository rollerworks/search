<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('rollerworks_search.doctrine_orm.factory', \Rollerworks\Component\Search\Doctrine\Orm\DoctrineOrmFactory::class)
        ->public()
        ->args([service('rollerworks_search.doctrine.cache')->nullOnInvalid()]);

    $services->set(\Rollerworks\Component\Search\Extension\Doctrine\Orm\Type\FieldTypeExtension::class)
        ->tag('rollerworks_search.type_extension', ['extended_type' => \Rollerworks\Component\Search\Extension\Core\Type\SearchFieldType::class]);

    $services->set(\Rollerworks\Component\Search\Extension\Doctrine\Orm\Type\BirthdayTypeExtension::class)
        ->tag('rollerworks_search.type_extension', ['extended_type' => \Rollerworks\Component\Search\Extension\Core\Type\BirthdayType::class]);

    $services->set(\Rollerworks\Component\Search\Extension\Doctrine\Orm\Type\ChildCountType::class)
        ->tag('rollerworks_search.type_extension', ['extended_type' => \Rollerworks\Component\Search\Extension\Doctrine\Dbal\Type\ChildCountType::class]);

    $services->set(\Rollerworks\Component\Search\Extension\Doctrine\Orm\Type\MoneyTypeExtension::class)
        ->tag('rollerworks_search.type_extension', ['extended_type' => \Rollerworks\Component\Search\Extension\Core\Type\MoneyType::class]);
};
