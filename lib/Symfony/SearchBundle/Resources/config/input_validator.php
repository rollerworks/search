<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function(ContainerConfigurator $container) {
    $services = $container->services();

    $services->set('rollerworks_search.validator', \Rollerworks\Component\Search\Extension\Symfony\Validator\InputValidator::class)
        ->args([service('validator')]);

    $services->set(\Rollerworks\Component\Search\Extension\Symfony\Validator\Type\FieldTypeValidatorExtension::class)
        ->tag('rollerworks_search.type_extension', ['extended_type' => \Rollerworks\Component\Search\Extension\Core\Type\SearchFieldType::class]);
};
