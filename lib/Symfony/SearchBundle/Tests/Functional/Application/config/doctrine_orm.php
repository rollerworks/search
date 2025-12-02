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

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {

    // TODO Convert doctrine_orm.yml to PHP

    $containerConfigurator->extension('doctrine', [
        'orm' => [
            'enable_native_lazy_objects' => \PHP_VERSION_ID >= 80400,
        ],
    ]);

};
