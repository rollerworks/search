<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Rollerscapes
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link    http://projects.rollerscapes.net/RollerFramework
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */

namespace Rollerworks\RecordFilterBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Adds services tagged as formatter-modifiers to the formatter's modifier registry.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class FormatterModifiersRegistryPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('rollerworks_record_filter.formatter_factory.modifiers_registry')) {
            return;
        }

        $postModifiers = array();

        foreach ($container->findTaggedServiceIds('rollerworks_record_filter.formatter_post_modifier') as $id => $attributes) {
            $priority = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;

            $postModifiers[$priority][] = new Reference($id);
        }

        $preModifiers = array();

        foreach ($container->findTaggedServiceIds('rollerworks_record_filter.formatter_pre_modifier') as $id => $attributes) {
            $priority = isset($attributes[0]['priority']) ? $attributes[0]['priority'] : 0;

            $preModifiers[$priority][] = new Reference($id);
        }

        // sort by priority and flatten
        if (count($postModifiers)) {
            krsort($postModifiers);
            $postModifiers  = call_user_func_array('array_merge', $postModifiers);
        }

        if (count($preModifiers)) {
            krsort($preModifiers);
            $preModifiers  = call_user_func_array('array_merge', $preModifiers);
        }

        $definition = $container->getDefinition('rollerworks_record_filter.formatter_factory.modifiers_registry');

        foreach ($postModifiers as $service) {
            $definition->addMethodCall('addPostModifier', array($service));
        }

        foreach ($preModifiers as $service) {
            $definition->addMethodCall('addPreModifier', array($service));
        }
    }
}
