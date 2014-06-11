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
 * Compiler pass to register tagged services for an input processor.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class InputProcessorPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('rollerworks_search.input_factory')) {
            return;
        }

        $inputProcessors = array();
        foreach ($container->findTaggedServiceIds('rollerworks_search.input_processor') as $serviceId => $tag) {
            $alias = isset($tag[0]['alias']) ? $tag[0]['alias'] : $serviceId;

            $container->findDefinition($serviceId)->setScope('prototype');
            $inputProcessors[$alias] = $serviceId;
        }

        $definition = $container->getDefinition('rollerworks_search.input_factory');
        $definition->replaceArgument(1, $inputProcessors);
    }
}
