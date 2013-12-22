<?php

/*
 * This file is part of the RollerworksSearchBundle package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\SearchBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

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
        $definition->replaceArgument(2, $inputProcessors);
    }
}
