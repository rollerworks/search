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
 * Compiler pass to register tagged services for an exporter.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class ExporterPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (! $container->hasDefinition('rollerworks_search.exporter_loader')) {
            return;
        }

        $exporters = [];
        $exportersServices = [];

        foreach ($container->findTaggedServiceIds('rollerworks_search.condition_exporter') as $serviceId => $tag) {
            if (! isset($tag[0]['format'])) {
                throw new InvalidArgumentException(sprintf('"rollerworks_search.condition_exporter" tagged services must have the format configured using the format attribute, none was configured for the "%s" service.', $serviceId));
            }

            $exporters[$tag[0]['format']] = $serviceId;
            $exportersServices[$serviceId] = new ServiceClosureArgument(new Reference($serviceId));
        }

        $definition = $container->getDefinition('rollerworks_search.exporter_loader');
        $definition->replaceArgument(0, (new Definition(ServiceLocator::class, [$exportersServices]))->addTag('container.service_locator'));
        $definition->replaceArgument(1, $exporters);
    }
}
