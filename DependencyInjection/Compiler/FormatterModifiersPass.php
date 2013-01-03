<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Adds services tagged as 'formatter_modifier' to the formatter service.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class FormatterModifiersPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('rollerworks_record_filter.modifier_formatter')) {
            return;
        }

        $modifiers = array();

        foreach ($container->findTaggedServiceIds('rollerworks_record_filter.formatter_modifier') as $id => $attributes) {
            $priority = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;

            $modifiers[$priority][] = new Reference($id);
        }

        // sort by priority and flatten
        if (count($modifiers)) {
            krsort($modifiers);
            $modifiers = call_user_func_array('array_merge', $modifiers);
        }

        $definition = $container->getDefinition('rollerworks_record_filter.modifier_formatter');

        foreach ($modifiers as $service) {
            $definition->addMethodCall('registerModifier', array($service));
        }
    }
}
