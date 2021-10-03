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

namespace Rollerworks\Component\Search\ApiPlatform\Metadata;

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;

/**
 * The DefaultConfigurationMetadataFactory merges the `_defaults` configuration
 * of the `rollerworks_search` resource attribute to all configs.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class DefaultConfigurationMetadataFactory implements ResourceMetadataFactoryInterface
{
    private $decorated;

    public function __construct(ResourceMetadataFactoryInterface $decorated)
    {
        $this->decorated = $decorated;
    }

    public function create(string $resourceClass): ResourceMetadata
    {
        $resourceMetadata = $this->decorated->create($resourceClass);
        $searchConfig = $resourceMetadata->getAttribute('rollerworks_search');

        if (empty($searchConfig) || empty($searchConfig['contexts']['_defaults'])) {
            return $resourceMetadata;
        }

        $configurations = $searchConfig['contexts'];
        unset($configurations['_defaults']);

        foreach ($configurations as $name => $configuration) {
            $configurations[$name] = array_replace_recursive($searchConfig['contexts']['_defaults'], $configuration);
        }

        $attributes = $resourceMetadata->getAttributes();
        $attributes['rollerworks_search']['contexts'] = $configurations;

        return $resourceMetadata->withAttributes($attributes);
    }
}
