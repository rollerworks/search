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

namespace Rollerworks\Component\Search\ApiPlatform\Tests\EventListener;

use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use Prophecy\Argument;
use Rollerworks\Component\Search\ApiPlatform\EventListener\SearchConditionListener;
use Rollerworks\Component\Search\ApiPlatform\SearchConditionEvent;
use Rollerworks\Component\Search\ApiPlatform\Tests\Fixtures\BookFieldSet;
use Rollerworks\Component\Search\ApiPlatform\Tests\Fixtures\Dummy;
use Rollerworks\Component\Search\ApiPlatform\Tests\Mock\SpyingInputProcessor;
use Rollerworks\Component\Search\ApiPlatform\Tests\Mock\StubInputProcessor;
use Rollerworks\Component\Search\Exception\UnexpectedTypeException;
use Rollerworks\Component\Search\InputProcessor;
use Rollerworks\Component\Search\Loader\ClosureContainer;
use Rollerworks\Component\Search\Loader\InputProcessorLoader;
use Rollerworks\Component\Search\Test\SearchIntegrationTestCase;
use Symfony\Component\Cache\Simple\ArrayCache;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class SearchConditionListenerTest extends SearchIntegrationTestCase
{
    /** @test */
    public function it_sets_search_condition_and_config_for_empty_qeury()
    {
        $dummyMetadata = new ResourceMetadata(
            'dummy',
            'dummy',
            '#dummy',
            [],
            [],
            [
                'rollerworks_search' => [
                    'contexts' => [
                        '_any' => [
                            'fieldset' => BookFieldSet::class,
                        ],
                    ],
                ],
            ]
        );

        $httpKernel = $this->createMock(HttpKernelInterface::class);
        $resourceMetadataFactory = $this->createResourceMetadata($dummyMetadata);

        $request = new Request([], [], ['_api_resource_class' => Dummy::class]);
        $eventDispatcher = $this->expectingCallEventDispatcher($request);

        $inputProcessor = new SpyingInputProcessor();
        $listener = new SearchConditionListener(
            $this->getFactory(),
            $this->createProcessorLoader($inputProcessor, 'norm_string_query'),
            $resourceMetadataFactory,
            $eventDispatcher
        );

        $listener->onKernelRequest($event = new GetResponseEvent($httpKernel, $request, HttpKernelInterface::MASTER_REQUEST));

        self::assertEquals(
            [
                '_api_resource_class' => Dummy::class,
                '_api_search_config' => ['fieldset' => BookFieldSet::class],
                '_api_search_context' => '_any',
                '_api_search_condition' => SpyingInputProcessor::getCondition(),
            ],
            $request->attributes->all()
        );
        self::assertEquals('', $inputProcessor->getInput());

        $config = $inputProcessor->getConfig();
        self::assertEquals(BookFieldSet::class, $config->getFieldSet()->getSetName());
    }

    /** @test */
    public function it_sets_search_condition_and_config_for_json_query()
    {
        $dummyMetadata = new ResourceMetadata(
            'dummy',
            'dummy',
            '#dummy',
            [],
            [],
            [
                'rollerworks_search' => [
                    'contexts' => [
                        '_any' => [
                            'fieldset' => BookFieldSet::class,
                        ],
                    ],
                ],
            ]
        );

        $httpKernel = $this->createMock(HttpKernelInterface::class);
        $resourceMetadataFactory = $this->createResourceMetadata($dummyMetadata);

        $request = new Request(['search' => '{"fields:": ["foobar"]}'], [], ['_api_resource_class' => Dummy::class]);
        $eventDispatcher = $this->expectingCallEventDispatcher($request);

        $inputProcessor = new SpyingInputProcessor();
        $listener = new SearchConditionListener(
            $this->getFactory(),
            $this->createProcessorLoader($inputProcessor, 'json'),
            $resourceMetadataFactory,
            $eventDispatcher
        );

        $listener->onKernelRequest($event = new GetResponseEvent($httpKernel, $request, HttpKernelInterface::MASTER_REQUEST));

        self::assertEquals(
            [
                '_api_resource_class' => Dummy::class,
                '_api_search_config' => ['fieldset' => BookFieldSet::class],
                '_api_search_context' => '_any',
                '_api_search_condition' => SpyingInputProcessor::getCondition(),
            ],
            $request->attributes->all()
        );
        self::assertEquals('{"fields:": ["foobar"]}', $inputProcessor->getInput());

        $config = $inputProcessor->getConfig();
        self::assertEquals(BookFieldSet::class, $config->getFieldSet()->getSetName());
    }

    /**
     * @test
     */
    public function it_requires_a_string_search_condition()
    {
        $dummyMetadata = new ResourceMetadata(
            'dummy',
            'dummy',
            '#dummy',
            [],
            [],
            [
                'rollerworks_search' => [
                    'contexts' => [
                        '_any' => [
                            'fieldset' => BookFieldSet::class,
                        ],
                    ],
                ],
            ]
        );

        $httpKernel = $this->createMock(HttpKernelInterface::class);
        $resourceMetadataFactory = $this->createResourceMetadata($dummyMetadata);

        $request = new Request(['search' => ['foobar' => 'he']], [], ['_api_resource_class' => Dummy::class]);
        $eventDispatcher = $this->expectingNoCallEventDispatcher();

        $inputProcessor = new SpyingInputProcessor();
        $listener = new SearchConditionListener(
            $this->getFactory(),
            $this->createProcessorLoader($inputProcessor, 'json'),
            $resourceMetadataFactory,
            $eventDispatcher
        );

        $this->expectException(UnexpectedTypeException::class);
        $this->expectExceptionMessage('Expected argument of type "string", "array" given');

        $listener->onKernelRequest($event = new GetResponseEvent($httpKernel, $request, HttpKernelInterface::MASTER_REQUEST));
    }

    /** @test */
    public function it_sets_search_condition_and_config_for_context()
    {
        $dummyMetadata = new ResourceMetadata(
            'dummy',
            'dummy',
            '#dummy',
            [],
            [],
            [
                'rollerworks_search' => [
                    'contexts' => [
                        '_any' => [
                            'fieldset' => 'book',
                        ],
                        'frontend' => [
                            'fieldset' => BookFieldSet::class,
                        ],
                    ],
                ],
            ]
        );

        $httpKernel = $this->createMock(HttpKernelInterface::class);
        $resourceMetadataFactory = $this->createResourceMetadata($dummyMetadata);

        $request = new Request([], [], ['_api_resource_class' => Dummy::class, '_api_search_context' => 'frontend']);
        $eventDispatcher = $this->expectingCallEventDispatcher($request);

        $inputProcessor = new SpyingInputProcessor();
        $listener = new SearchConditionListener(
            $this->getFactory(),
            $this->createProcessorLoader($inputProcessor, 'norm_string_query'),
            $resourceMetadataFactory,
            $eventDispatcher
        );

        $listener->onKernelRequest($event = new GetResponseEvent($httpKernel, $request, HttpKernelInterface::MASTER_REQUEST));

        self::assertEquals(
            [
                '_api_resource_class' => Dummy::class,
                '_api_search_config' => ['fieldset' => BookFieldSet::class],
                '_api_search_context' => 'frontend',
                '_api_search_condition' => SpyingInputProcessor::getCondition(),
            ],
            $request->attributes->all()
        );
        self::assertEquals('', $inputProcessor->getInput());

        $config = $inputProcessor->getConfig();
        self::assertEquals(BookFieldSet::class, $config->getFieldSet()->getSetName());
    }

    /** @test */
    public function it_does_nothing_when_no_metadata_is_set()
    {
        $dummyMetadata = new ResourceMetadata('dummy', 'dummy', '#dummy');

        $httpKernel = $this->createMock(HttpKernelInterface::class);
        $resourceMetadataFactory = $this->createResourceMetadata($dummyMetadata);

        $eventDispatcher = $this->expectingNoCallEventDispatcher();
        $listener = new SearchConditionListener(
            $this->getFactory(),
            $this->createProcessorLoader(new StubInputProcessor(), 'noop'),
            $resourceMetadataFactory,
            $eventDispatcher
        );

        $request = new Request([], [], ['_api_resource_class' => Dummy::class]);
        $listener->onKernelRequest($event = new GetResponseEvent($httpKernel, $request, HttpKernelInterface::MASTER_REQUEST));

        self::assertEquals(['_api_resource_class' => Dummy::class], $request->attributes->all());
    }

    /** @test */
    public function it_does_nothing_not_cacheable_requests()
    {
        $httpKernel = $this->createMock(HttpKernelInterface::class);

        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Argument::any())->shouldNotBeCalled();
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $request = new Request([], [], ['_api_resource_class' => Dummy::class]);
        $request->setMethod('POST');

        $eventDispatcher = $this->expectingNoCallEventDispatcher();
        $listener = new SearchConditionListener(
            $this->getFactory(),
            $this->createProcessorLoader(new StubInputProcessor(), 'noop'),
            $resourceMetadataFactory,
            $eventDispatcher
        );

        $listener->onKernelRequest($event = new GetResponseEvent($httpKernel, $request, HttpKernelInterface::MASTER_REQUEST));

        self::assertEquals(['_api_resource_class' => Dummy::class], $request->attributes->all());
    }

    /** @test */
    public function it_maps_configuration_processor_configuration()
    {
        $dummyMetadata = new ResourceMetadata(
            'dummy',
            'dummy',
            '#dummy',
            [],
            [],
            [
                'rollerworks_search' => [
                    'contexts' => [
                        '_any' => [
                            'fieldset' => BookFieldSet::class,
                            'processor' => [
                                'cache_ttl' => 30,
                            ],
                        ],
                    ],
                ],
            ]
        );

        $httpKernel = $this->createMock(HttpKernelInterface::class);
        $resourceMetadataFactory = $this->createResourceMetadata($dummyMetadata);

        $request = new Request([], [], ['_api_resource_class' => Dummy::class]);
        $eventDispatcher = $this->expectingCallEventDispatcher($request);

        $inputProcessor = new SpyingInputProcessor();
        $listener = new SearchConditionListener(
            $this->getFactory(),
            $this->createProcessorLoader($inputProcessor, 'norm_string_query'),
            $resourceMetadataFactory,
            $eventDispatcher
        );

        $listener->onKernelRequest($event = new GetResponseEvent($httpKernel, $request, HttpKernelInterface::MASTER_REQUEST));

        self::assertEquals(
            [
                '_api_resource_class' => Dummy::class,
                '_api_search_config' => [
                    'fieldset' => BookFieldSet::class,
                    'processor' => ['cache_ttl' => 30],
                ],
                '_api_search_context' => '_any',
                '_api_search_condition' => SpyingInputProcessor::getCondition(),
            ],
            $request->attributes->all()
        );

        $config = $inputProcessor->getConfig();
        self::assertEquals(BookFieldSet::class, $config->getFieldSet()->getSetName());
        self::assertEquals(30, $config->getCacheTTL());
    }

    /** @test */
    public function it_stores_cached_result_when_configured()
    {
        $dummyMetadata = new ResourceMetadata(
            'dummy',
            'dummy',
            '#dummy',
            [],
            [],
            [
                'rollerworks_search' => [
                    'contexts' => [
                        '_any' => [
                            'fieldset' => BookFieldSet::class,
                            'processor' => [
                                'cache_ttl' => 30,
                            ],
                        ],
                    ],
                ],
            ]
        );

        $httpKernel = $this->createMock(HttpKernelInterface::class);
        $resourceMetadataFactory = $this->createResourceMetadata($dummyMetadata);

        $request = new Request([], [], ['_api_resource_class' => Dummy::class]);
        $eventDispatcher = $this->expectingCallEventDispatcher($request);

        $inputProcessor = new SpyingInputProcessor();
        $listener = new SearchConditionListener(
            $this->getFactory(),
            $this->createProcessorLoader($inputProcessor, 'norm_string_query'),
            $resourceMetadataFactory,
            $eventDispatcher,
            $cache = new ArrayCache()
        );

        $listener->onKernelRequest($event = new GetResponseEvent($httpKernel, $request, HttpKernelInterface::MASTER_REQUEST));

        self::assertEquals(
            [
                '_api_resource_class' => Dummy::class,
                '_api_search_config' => [
                    'fieldset' => BookFieldSet::class,
                    'processor' => ['cache_ttl' => 30],
                ],
                '_api_search_context' => '_any',
                '_api_search_condition' => SpyingInputProcessor::getCondition(),
            ],
            $request->attributes->all()
        );
        self::assertCount(1, $cache->getValues());

        $config = $inputProcessor->getConfig();
        self::assertEquals(BookFieldSet::class, $config->getFieldSet()->getSetName());
        self::assertEquals(30, $config->getCacheTTL());
    }

    /** @test */
    public function it_does_not_store_cached_result_when_ttl_is_unconfigured()
    {
        $dummyMetadata = new ResourceMetadata(
            'dummy',
            'dummy',
            '#dummy',
            [],
            [],
            [
                'rollerworks_search' => [
                    'contexts' => [
                        '_any' => [
                            'fieldset' => BookFieldSet::class,
                        ],
                    ],
                ],
            ]
        );

        $httpKernel = $this->createMock(HttpKernelInterface::class);
        $resourceMetadataFactory = $this->createResourceMetadata($dummyMetadata);

        $request = new Request([], [], ['_api_resource_class' => Dummy::class]);
        $eventDispatcher = $this->expectingCallEventDispatcher($request);

        $inputProcessor = new SpyingInputProcessor();
        $listener = new SearchConditionListener(
            $this->getFactory(),
            $this->createProcessorLoader($inputProcessor, 'norm_string_query'),
            $resourceMetadataFactory,
            $eventDispatcher,
            $cache = new ArrayCache()
        );

        $listener->onKernelRequest($event = new GetResponseEvent($httpKernel, $request, HttpKernelInterface::MASTER_REQUEST));

        self::assertEquals(
            [
                '_api_resource_class' => Dummy::class,
                '_api_search_config' => [
                    'fieldset' => BookFieldSet::class,
                ],
                '_api_search_context' => '_any',
                '_api_search_condition' => SpyingInputProcessor::getCondition(),
            ],
            $request->attributes->all()
        );
        self::assertCount(0, $cache->getValues());

        $config = $inputProcessor->getConfig();
        self::assertEquals(BookFieldSet::class, $config->getFieldSet()->getSetName());
        self::assertNull($config->getCacheTTL());
    }

    /**
     * @test
     * @dataProvider provideInvalidConfigurations
     */
    public function it_errors_when_context_configuration_is_invalid(string $message, array $config)
    {
        $dummyMetadata = new ResourceMetadata(
            'dummy',
            'dummy',
            '#dummy',
            [],
            [],
            $config
        );

        $httpKernel = $this->createMock(HttpKernelInterface::class);
        $resourceMetadataFactory = $this->createResourceMetadata($dummyMetadata);

        $request = new Request(
            ['search' => ['fields' => ['id' => ['single-values' => [1, 1]]]]],
            [],
            ['_api_resource_class' => Dummy::class]
        );

        $eventDispatcher = $this->expectingNoCallEventDispatcher();
        $listener = new SearchConditionListener(
            $this->getFactory(),
            $this->createProcessorLoader(new StubInputProcessor(), 'noop'),
            $resourceMetadataFactory,
            $eventDispatcher
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($message);

        $listener->onKernelRequest($event = new GetResponseEvent($httpKernel, $request, HttpKernelInterface::MASTER_REQUEST));
    }

    public function provideInvalidConfigurations(): array
    {
        $resourceClass = Dummy::class;

        return [
            'context with missing fieldset' => [
                'Search context "_any" is incorrectly configured for Resource "'.$resourceClass.'#attributes[rollerworks_search]", missing a "fieldset" reference.',
                [
                    'rollerworks_search' => [
                        'contexts' => [
                            '_any' => [
                                'processor' => ['cache_ttl' => '30'],
                            ],
                        ],
                    ],
                ],
            ],
            'unsupported processor option' => [
                'Processor option "foo" is not supported for Resource "'.$resourceClass.'".',
                [
                    'rollerworks_search' => [
                        'contexts' => [
                            '_any' => [
                                'fieldset' => BookFieldSet::class,
                                'processor' => ['cache_ttl' => '30', 'foo' => 'bar'],
                            ],
                        ],
                    ],
                ],
            ],
            'unregistered context' => [
                'Search context "_any" is not supported for Resource "'.$resourceClass.'#attributes[rollerworks_search][contexts]", supported: "foo", "bar".',
                [
                    'rollerworks_search' => [
                        'contexts' => [
                            'foo' => [
                                'fieldset' => BookFieldSet::class,
                                'processor' => ['cache_ttl' => '30', 'foo' => 'bar'],
                            ],
                            'bar' => [
                                'fieldset' => BookFieldSet::class,
                                'processor' => ['cache_ttl' => '30', 'foo' => 'bar'],
                            ],
                        ],
                    ],
                ],
            ],
            'contexts' => [
                'Resource "'.$resourceClass.'#attributes[rollerworks_search]" is missing a contexts array. Add a "contexts" array with at least one entry.',
                [
                    'rollerworks_search' => [
                        '_contexts' => [
                            'foo' => [
                                'fieldset' => BookFieldSet::class,
                                'processor' => ['cache_ttl' => '30', 'foo' => 'bar'],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    private function createResourceMetadata(ResourceMetadata $metadata = null, string $resourceClass = Dummy::class): ResourceMetadataFactoryInterface
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create($resourceClass)->shouldBeCalled()->willReturn($metadata);

        return $resourceMetadataFactoryProphecy->reveal();
    }

    private function expectingCallEventDispatcher(Request $request): EventDispatcherInterface
    {
        $event = new SearchConditionEvent(SpyingInputProcessor::getCondition(), Dummy::class, $request);

        $eventDispatcherProphecy = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcherProphecy->dispatch(SearchConditionEvent::SEARCH_CONDITION_EVENT, $event)->shouldBeCalled();
        $eventDispatcherProphecy->dispatch(SearchConditionEvent::SEARCH_CONDITION_EVENT.Dummy::class, $event)->shouldBeCalled();

        return $eventDispatcherProphecy->reveal();
    }

    private function expectingNoCallEventDispatcher(): EventDispatcherInterface
    {
        $eventDispatcherProphecy = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcherProphecy->dispatch(SearchConditionEvent::SEARCH_CONDITION_EVENT, Argument::any())->shouldNotBeCalled();
        $eventDispatcherProphecy->dispatch(SearchConditionEvent::SEARCH_CONDITION_EVENT.Dummy::class, Argument::any())->shouldNotBeCalled();

        return $eventDispatcherProphecy->reveal();
    }

    private function createProcessorLoader(InputProcessor $inputProcessor, string $name): InputProcessorLoader
    {
        return new InputProcessorLoader(
            new ClosureContainer(
                [
                    $name => function () use ($inputProcessor) {
                        return $inputProcessor;
                    },
                ]
            ),
            [$name => $name]
        );
    }
}
