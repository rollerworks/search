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

use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use PHPUnit\Framework\TestCase;
use Rollerworks\Component\Search\ApiPlatform\Metadata\DefaultConfigurationMetadataFactory;

final class DefaultConfigurationMetadataFactoryTest extends TestCase
{
    /** @test */
    public function it_merges_defaults_into_all_configs()
    {
        $resourceMetadata = new ResourceMetadata(null, 'My desc', null, null, null, [
            'rollerworks_search' => [
                'contexts' => [
                    '_default' => [
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

        $decoratedProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $decoratedProphecy->create('Foo')->willReturn($resourceMetadata)->shouldBeCalled();
        $decorated = $decoratedProphecy->reveal();

        $factory = new DefaultConfigurationMetadataFactory($decorated);

        $resourceMetadata = new ResourceMetadata(null, 'My desc', null, null, null, [
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

        self::assertEquals($resourceMetadata, $factory->create('Foo'));
    }

    /** @test */
    public function it_returns_without_updating_when_defaults_were_set()
    {
        $resourceMetadata = new ResourceMetadata(null, 'My desc', null, null, null, [
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

        $decoratedProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $decoratedProphecy->create('Foo')->willReturn($resourceMetadata)->shouldBeCalled();
        $decorated = $decoratedProphecy->reveal();

        $factory = new DefaultConfigurationMetadataFactory($decorated);

        self::assertSame($resourceMetadata, $factory->create('Foo'));
    }

    /** @test */
    public function it_returns_without_updating_when_no_search_config_was_set()
    {
        $resourceMetadata = new ResourceMetadata(null, 'My desc', null, null, null, [
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
        ]);

        $decoratedProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $decoratedProphecy->create('Foo')->willReturn($resourceMetadata)->shouldBeCalled();
        $decorated = $decoratedProphecy->reveal();

        $factory = new DefaultConfigurationMetadataFactory($decorated);

        self::assertSame($resourceMetadata, $factory->create('Foo'));
    }
}
