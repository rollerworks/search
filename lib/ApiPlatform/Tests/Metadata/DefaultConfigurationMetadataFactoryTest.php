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

namespace Rollerworks\Component\Search\ApiPlatform\Tests\Metadata;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Resource\ResourceMetadataCollection;
use PHPUnit\Framework\TestCase;
use Rollerworks\Component\Search\ApiPlatform\Metadata\DefaultConfigurationMetadataFactory;
use Rollerworks\Component\Search\ApiPlatform\Tests\MetadataFactoryTrait;

/**
 * @internal
 */
final class DefaultConfigurationMetadataFactoryTest extends TestCase
{
    use MetadataFactoryTrait;

    /** @test */
    public function it_merges_defaults_into_all_configs(): void
    {
        $decorated = $this->createResourceMetadataFactory([
            'rollerworks_search' => [
                'contexts' => [
                    '_defaults' => [
                        'processor' => [
                            'cache_ttl' => 60,
                            'export_format' => 'json',
                        ],
                        'doctrine_orm' => [
                            'mappings' => [
                                'dummy-id' => 'id',
                                'dummy-name' => ['property' => 'name'],
                            ],
                        ],
                    ],
                    'foo' => [
                        'processor' => [
                            'export_format' => 'array',
                        ],
                        'doctrine_orm' => [
                            'mappings' => [
                                'dummy-name' => ['property' => 'userName'],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $factory = new DefaultConfigurationMetadataFactory($decorated);

        $resourceMetadata = $this->getMetadataCollection([
            'rollerworks_search' => [
                'contexts' => [
                    'foo' => [
                        'processor' => [
                            'cache_ttl' => 60,
                            'export_format' => 'array',
                        ],
                        'doctrine_orm' => [
                            'mappings' => [
                                'dummy-id' => 'id',
                                'dummy-name' => ['property' => 'userName'],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        self::assertEquals($resourceMetadata, $factory->create('dummy'));
    }

    /** @test */
    public function it_returns_without_updating_when_no_defaults_were_set(): void
    {
        $resourceMetadata = $this->getMetadataCollection([
            'rollerworks_search' => [
                'contexts' => [
                    'foo' => [
                        'processor' => [
                            'export_format' => 'array',
                        ],
                        'doctrine_orm' => [
                            'mappings' => [
                                'dummy-name' => ['property' => 'userName'],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $decorated = $this->createResourceMetadataFactory($resourceMetadata, 'Foo');

        $factory = new DefaultConfigurationMetadataFactory($decorated);

        self::assertSame($resourceMetadata, $factory->create('Foo'));
    }

    /** @test */
    public function it_returns_without_updating_when_no_search_config_was_set(): void
    {
        $resourceMetadata = $this->getMetadataCollection([]);

        $decorated = $this->createResourceMetadataFactory($resourceMetadata, 'Foo');
        $factory = new DefaultConfigurationMetadataFactory($decorated);

        self::assertSame($resourceMetadata, $factory->create('Foo'));
    }

    /** @test */
    public function it_returns_when_no_operations_were_set(): void
    {
        $resourceMetadata = new ResourceMetadataCollection('Foo', [new ApiResource()]);

        $decorated = $this->createResourceMetadataFactory($resourceMetadata, 'Foo');
        $factory = new DefaultConfigurationMetadataFactory($decorated);

        self::assertSame($resourceMetadata, $factory->create('Foo'));
    }
}
