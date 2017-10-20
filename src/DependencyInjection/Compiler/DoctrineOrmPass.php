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

use Rollerworks\Component\Search\Doctrine\Orm\Functions\SqlFieldConversion;
use Rollerworks\Component\Search\Doctrine\Orm\Functions\SqlValueConversion;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Compiler pass to register tagged services for an exporter.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class DoctrineOrmPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasParameter('rollerworks_search.doctrine.orm.entity_managers') ||
            !$container->hasParameter('doctrine.default_entity_manager')
        ) {
            return;
        }

        $entityManagers = $container->getParameterBag()->resolveValue(
            $container->getParameter('rollerworks_search.doctrine.orm.entity_managers')
        );

        // Assume Doctrine ORM is not enabled in the DoctrineBundle
        if (['default'] === $entityManagers && !$container->hasDefinition('doctrine.orm.default_configuration')) {
            return;
        }

        foreach ($entityManagers as $entityManager) {
            $ormConfigDef = $container->findDefinition('doctrine.orm.'.$entityManager.'_configuration');
            $ormConfigDef->addMethodCall('addCustomStringFunction', ['RW_SEARCH_FIELD_CONVERSION', SqlFieldConversion::class]);
            $ormConfigDef->addMethodCall('addCustomStringFunction', ['RW_SEARCH_VALUE_CONVERSION', SqlValueConversion::class]);
        }
    }
}
