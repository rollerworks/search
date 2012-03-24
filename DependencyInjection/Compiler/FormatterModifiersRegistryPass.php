<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Rollerscapes
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
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
            $definition->addMethodCall('registerPostModifier', array($service));
        }

        foreach ($preModifiers as $service) {
            $definition->addMethodCall('registerPreModifier', array($service));
        }
    }
}
