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

/**
 * Adds services tagged as 'rollerworks_record_filter.filter_type'
 * to the 'types_factory' service.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class AddFilterTypesPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('rollerworks_record_filter.types_factory')) {
            return;
        }

        $types = array();
        foreach ($container->findTaggedServiceIds('rollerworks_record_filter.filter_type') as $id => $attributes) {
            if (isset($attributes[0]['alias'])) {
                $types[$attributes[0]['alias']] = $id;

                // Set scope to prototype to make sure an unique instance is returned
                if ('prototype' !== $container->getDefinition($id)->getScope()) {
                    $container->getDefinition($id)->setScope('prototype');
                }
            }
        }

        $container->getDefinition('rollerworks_record_filter.types_factory')->replaceArgument(1, $types);
    }
}
