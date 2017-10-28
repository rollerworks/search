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

use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

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
        if (!$container->hasDefinition('rollerworks_search.input_loader')) {
            return;
        }

        $inputProcessors = [];
        $inputProcessorServices = [];

        foreach ($container->findTaggedServiceIds('rollerworks_search.input_processor') as $serviceId => $tag) {
            if (!isset($tag[0]['format'])) {
                throw new InvalidArgumentException(sprintf('"rollerworks_search.input_processor" tagged services must have the format configured using the format attribute, none was configured for the "%s" service.', $serviceId));
            }

            $inputProcessorServices[$serviceId] = new ServiceClosureArgument(new Reference($serviceId));
            $inputProcessors[$tag[0]['format']] = $serviceId;
        }

        $definition = $container->getDefinition('rollerworks_search.input_loader');
        $definition->replaceArgument(0, (new Definition(ServiceLocator::class, [$inputProcessorServices]))->addTag('container.service_locator'));
        $definition->replaceArgument(1, $inputProcessors);
    }
}
