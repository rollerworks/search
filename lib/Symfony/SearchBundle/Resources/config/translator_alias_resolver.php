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

use Rollerworks\Bundle\SearchBundle\TranslatorBasedAliasResolver;
use Rollerworks\Bundle\SearchBundle\Type\TranslatableFieldTypeExtension;
use Rollerworks\Bundle\SearchBundle\Type\TranslatableOrderFieldTypeExtension;
use Rollerworks\Component\Search\Extension\Core\Type\SearchFieldType;
use Rollerworks\Component\Search\Field\OrderFieldType;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('rollerworks_search.translator_based_alias_resolver', TranslatorBasedAliasResolver::class)
        ->args([service('translator')]);

    $services->set(TranslatableFieldTypeExtension::class)
        ->tag('rollerworks_search.type_extension', ['extended_type' => SearchFieldType::class]);

    $services->set(TranslatableOrderFieldTypeExtension::class)
        ->tag('rollerworks_search.type_extension', ['extended_type' => OrderFieldType::class]);
};
