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

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Compiler pass to register tagged services for a chain-formatter.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class ConditionOptimizerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('rollerworks_search.chain_condition_optimizer')) {
            return;
        }

        $definition = $container->getDefinition('rollerworks_search.chain_condition_optimizer');

        foreach ($container->findTaggedServiceIds('rollerworks_search.condition_optimizer') as $serviceId => $def) {
            $definition->addMethodCall('addOptimizer', [new Reference($serviceId)]);
        }
    }
}
