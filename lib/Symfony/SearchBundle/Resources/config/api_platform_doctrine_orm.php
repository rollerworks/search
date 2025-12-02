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

use Rollerworks\Component\Search\ApiPlatform\Doctrine\Orm\Extension\SearchExtension;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('rollerworks_search.api_platform.doctrine.orm.query_extension.search', SearchExtension::class)
        ->args([
            service('request_stack'),
            service('rollerworks_search.doctrine_orm.factory'),
        ])
        ->tag('api_platform.doctrine.orm.query_extension.collection', ['priority' => 32])
    ;
};
