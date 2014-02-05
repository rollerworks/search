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

/**
 * Compiler pass to register tagged services for an exporter.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class ExporterPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('rollerworks_search.exporter_factory')) {
            return;
        }

        $exporters = array();
        foreach ($container->findTaggedServiceIds('rollerworks_search.exporter') as $serviceId => $tag) {
            $alias = isset($tag[0]['alias']) ? $tag[0]['alias'] : $serviceId;
            $exporters[$alias] = $serviceId;
        }

        $definition = $container->getDefinition('rollerworks_search.exporter_factory');
        $definition->replaceArgument(1, $exporters);
    }
}
