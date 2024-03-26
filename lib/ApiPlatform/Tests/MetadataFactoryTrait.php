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

namespace Rollerworks\Component\Search\ApiPlatform\Tests;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use Prophecy\PhpUnit\ProphecyTrait;
use Rollerworks\Component\Search\ApiPlatform\Tests\Fixtures\BookFieldSet;

/**
 * @internal
 */
trait MetadataFactoryTrait
{
    use ProphecyTrait;

    private function createResourceMetadataFactory(array|ResourceMetadataCollection $metadata = null, string $resourceClass = 'dummy'): ResourceMetadataCollectionFactoryInterface
    {
        if (\is_array($metadata)) {
            $metadata = $this->getMetadataCollection($metadata, $resourceClass);
        }

        $metadata ??= $this->getMetadataCollection([
            'rollerworks_search' => [
                'contexts' => [
                    '_any' => [
                        'fieldset' => BookFieldSet::class,
                    ],
                ],
            ],
        ], $resourceClass);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataCollectionFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create($resourceClass)->willReturn($metadata);

        return $resourceMetadataFactoryProphecy->reveal();
    }

    private function getMetadataCollection(array $extraProperties, string $resourceClass = 'dummy'): ResourceMetadataCollection
    {
        return new ResourceMetadataCollection($resourceClass, [
            new ApiResource(operations: [
                'get' => new Get(class: $resourceClass, name: 'get'),
                'get_collection' => (new GetCollection(class: $resourceClass, name: 'get_collection'))->withExtraProperties($extraProperties),
            ]),
        ]);
    }
}
