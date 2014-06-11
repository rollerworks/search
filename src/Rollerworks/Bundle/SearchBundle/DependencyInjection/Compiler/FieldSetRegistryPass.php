<?php

/**
 * This file is part of the RollerworksSearchBundle package.
 *
 * (c) 2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Bundle\SearchBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

/**
 * Compiler pass to register tagged fieldsets for the FieldSetRegistry.
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

        $fieldsets = array();
        foreach ($container->findTaggedServiceIds('rollerworks_search.fieldset') as $serviceId => $tag) {
            $name = isset($tag[0]['name']) ? $tag[0]['name'] : $serviceId;
            $fieldsets[$name] = $serviceId;
        }

        $definition = $container->getDefinition('rollerworks_search.fieldset_registry');
        $definition->replaceArgument(1, $fieldsets);
    }
}
