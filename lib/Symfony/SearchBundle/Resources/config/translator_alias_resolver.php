<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('rollerworks_search.translator_based_alias_resolver', \Rollerworks\Bundle\SearchBundle\TranslatorBasedAliasResolver::class)
        ->args([service('translator')]);

    $services->set(\Rollerworks\Bundle\SearchBundle\Type\TranslatableFieldTypeExtension::class)
        ->tag('rollerworks_search.type_extension', ['extended_type' => \Rollerworks\Component\Search\Extension\Core\Type\SearchFieldType::class]);

    $services->set(\Rollerworks\Bundle\SearchBundle\Type\TranslatableOrderFieldTypeExtension::class)
        ->tag('rollerworks_search.type_extension', ['extended_type' => \Rollerworks\Component\Search\Field\OrderFieldType::class]);
};
