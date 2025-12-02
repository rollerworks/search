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

use Rollerworks\Component\Search\Input\JsonInput;
use Rollerworks\Component\Search\Input\NormStringQueryInput;
use Rollerworks\Component\Search\Input\StringQueryInput;
use Rollerworks\Component\Search\Loader\InputProcessorLoader;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('rollerworks_search.input_loader', InputProcessorLoader::class)
        ->args([
            null,
            [], // services with tag "rollerworks_search.input_processor" are inserted here by InputProcessorPass
        ]);

    $services->alias(InputProcessorLoader::class, 'rollerworks_search.input_loader');

    $services->set('rollerworks_search.input.abstract', StringQueryInput::class)
        ->abstract()
        ->args([service('rollerworks_search.validator')->nullOnInvalid()]);

    $services->set('rollerworks_search.input.string_query', StringQueryInput::class)
        ->parent('rollerworks_search.input.abstract')
        ->args([service('rollerworks_search.translator_based_alias_resolver')->nullOnInvalid()])
        ->tag('rollerworks_search.input_processor', ['format' => 'string_query']);

    $services->set('rollerworks_search.input.norm_string_query', NormStringQueryInput::class)
        ->parent('rollerworks_search.input.abstract')
        ->tag('rollerworks_search.input_processor', ['format' => 'norm_string_query']);

    $services->set('rollerworks_search.input.json', JsonInput::class)
        ->parent('rollerworks_search.input.abstract')
        ->tag('rollerworks_search.input_processor', ['format' => 'json']);
};
