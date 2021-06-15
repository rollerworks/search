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

namespace Rollerworks\Bundle\SearchBundle\DependencyInjection\Compiler;

use Rollerworks\Component\Search\Doctrine\Orm\Extension\Functions\AgeFunction;
use Rollerworks\Component\Search\Doctrine\Orm\Extension\Functions\CastFunction;
use Rollerworks\Component\Search\Doctrine\Orm\Extension\Functions\CountChildrenFunction;
use Rollerworks\Component\Search\Doctrine\Orm\Extension\Functions\MoneyCastFunction;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Compiler pass to register Doctrine ORM custom string functions.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class DoctrineOrmPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasParameter('rollerworks_search.doctrine.orm.entity_managers')
            || ! $container->hasParameter('doctrine.default_entity_manager')
        ) {
            return;
        }

        $entityManagers = $container->getParameterBag()->resolveValue(
            $container->getParameter('rollerworks_search.doctrine.orm.entity_managers')
        );

        // Assume Doctrine ORM is not enabled in the DoctrineBundle
        if (['default'] === $entityManagers && ! $container->hasDefinition('doctrine.orm.default_configuration')) {
            return;
        }

        foreach ($entityManagers as $entityManager) {
            $ormConfigDef = $container->findDefinition('doctrine.orm.' . $entityManager . '_configuration');
            $ormConfigDef->addMethodCall('addCustomStringFunction', ['SEARCH_CONVERSION_CAST', CastFunction::class]);
            $ormConfigDef->addMethodCall('addCustomNumericFunction', ['SEARCH_CONVERSION_AGE', AgeFunction::class]);
            $ormConfigDef->addMethodCall('addCustomNumericFunction', ['SEARCH_COUNT_CHILDREN', CountChildrenFunction::class]);
            $ormConfigDef->addMethodCall('addCustomNumericFunction', ['SEARCH_MONEY_AS_NUMERIC', MoneyCastFunction::class]);
        }
    }
}
