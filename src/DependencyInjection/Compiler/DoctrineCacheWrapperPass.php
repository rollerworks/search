<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Bundle\SearchBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * Checks if the `rollerworks_search.metadata.cache_driver` is a Doctrine Cache implementation
 * and wraps it in a `Rollerworks\Component\Metadata\Cache\DoctrineCache` class.
 */
final class DoctrineCacheWrapperPass implements CompilerPassInterface
{
    const SERVICE_ID = 'rollerworks_search.metadata.cache_driver';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->has(self::SERVICE_ID)) {
            return;
        }

        $metadataCache = $container->findDefinition(self::SERVICE_ID);

        if (in_array('Doctrine\Common\Cache\Cache', class_implements($metadataCache->getClass()), true)) {
            $doctrineCacheWrapper = new Definition('Rollerworks\Component\Metadata\Cache\DoctrineCache', [$metadataCache]);
            $container->setDefinition(self::SERVICE_ID, $doctrineCacheWrapper)->setPublic(false);
        }
    }
}
