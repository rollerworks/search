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

use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Compiler pass to register tagged FieldSet's for the FieldSetRegistry.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class FieldSetRegistryPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('rollerworks_search.fieldset_registry')) {
            return;
        }

        $fieldSetServices = [];
        $fieldSetServiceIds = [];

        foreach ($container->findTaggedServiceIds('rollerworks_search.fieldset') as $serviceId => $tag) {
            $class = $container->findDefinition($serviceId)->getClass();

            $fieldSetServices[$class] = new Reference($serviceId);
            $fieldSetServiceIds[$class] = $serviceId;
        }

        $definition = $container->getDefinition('rollerworks_search.fieldset_registry');
        $definition->replaceArgument(0, new ServiceLocatorArgument($fieldSetServices));
        $definition->replaceArgument(1, $fieldSetServiceIds);
    }
}
