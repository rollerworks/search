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

use ApiPlatform\Exception\RuntimeException;
use Prophecy\Argument;
use Rollerworks\Component\Search\ApiPlatform\EventListener\SearchConditionListener;
use Rollerworks\Component\Search\ApiPlatform\SearchConditionEvent;
use Rollerworks\Component\Search\ApiPlatform\Tests\Fixtures\BookFieldSet;
use Rollerworks\Component\Search\ApiPlatform\Tests\MetadataFactoryTrait;
use Rollerworks\Component\Search\ApiPlatform\Tests\Mock\SpyingInputProcessor;
use Rollerworks\Component\Search\ApiPlatform\Tests\Mock\StubInputProcessor;
use Rollerworks\Component\Search\InputProcessor;
use Rollerworks\Component\Search\Loader\ClosureContainer;
use Rollerworks\Component\Search\Loader\InputProcessorLoader;
use Rollerworks\Component\Search\Test\SearchIntegrationTestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * @internal
 */
final class SearchConditionListenerTest extends SearchIntegrationTestCase
{
    use MetadataFactoryTrait;

    /** @test */
    public function it_sets_search_condition_and_config_for_empty_query(): void
    {
        $httpKernel = $this->createMock(HttpKernelInterface::class);
        $resourceMetadataFactory = $this->createResourceMetadataFactory();

        $request = new Request([], [], ['_api_resource_class' => 'dummy', '_api_operation_name' => 'get_collection']);
        $eventDispatcher = $this->expectingCallEventDispatcher($request);

        $inputProcessor = new SpyingInputProcessor();
        $listener = new SearchConditionListener(
            $this->getFactory(),
            $this->createProcessorLoader($inputProcessor, 'norm_string_query'),
            $resourceMetadataFactory,
            $eventDispatcher
        );

        $listener->onKernelRequest(new RequestEvent($httpKernel, $request, HttpKernelInterface::MAIN_REQUEST));

        $arr = [
            '_api_resource_class' => 'dummy',
            '_api_operation_name' => 'get_collection',
            '_api_search_config' => ['fieldset' => BookFieldSet::class],
            '_api_search_context' => '_any',
            '_api_search_condition' => SpyingInputProcessor::getCondition(),
        ];

        self::assertOperationAttrEquals($arr, $request);
        self::assertEquals('', $inputProcessor->getInput());

        self::assertEquals(BookFieldSet::class, $inputProcessor->getConfig()->getFieldSet()->getSetName());
    }

    /** @test */
    public function it_sets_search_condition_and_config_for_json_query(): void
    {
        $httpKernel = $this->createMock(HttpKernelInterface::class);
        $resourceMetadataFactory = $this->createResourceMetadataFactory();

        $request = new Request(['search' => '{"fields:": ["foobar"]}'], [], ['_api_resource_class' => 'dummy', '_api_operation_name' => 'get_collection']);
        $eventDispatcher = $this->expectingCallEventDispatcher($request);

        $inputProcessor = new SpyingInputProcessor();
        $listener = new SearchConditionListener(
            $this->getFactory(),
            $this->createProcessorLoader($inputProcessor, 'json'),
            $resourceMetadataFactory,
            $eventDispatcher
        );

        $listener->onKernelRequest(new RequestEvent($httpKernel, $request, HttpKernelInterface::MAIN_REQUEST));

        self::assertOperationAttrEquals(
            [
                '_api_resource_class' => 'dummy',
                '_api_operation_name' => 'get_collection',
                '_api_search_config' => ['fieldset' => BookFieldSet::class],
                '_api_search_context' => '_any',
                '_api_search_condition' => SpyingInputProcessor::getCondition(),
            ],
            $request
        );
        self::assertEquals('{"fields:": ["foobar"]}', $inputProcessor->getInput());

        $config = $inputProcessor->getConfig();
        self::assertEquals(BookFieldSet::class, $config->getFieldSet()->getSetName());
    }

    /** @test */
    public function it_sets_search_condition_and_config_for_context(): void
    {
        $httpKernel = $this->createMock(HttpKernelInterface::class);
        $resourceMetadataFactory = $this->createResourceMetadataFactory([
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
        ]);

        $request = new Request([], [], ['_api_resource_class' => 'dummy', '_api_search_context' => 'frontend', '_api_operation_name' => 'get_collection']);
        $eventDispatcher = $this->expectingCallEventDispatcher($request);

        $inputProcessor = new SpyingInputProcessor();
        $listener = new SearchConditionListener(
            $this->getFactory(),
            $this->createProcessorLoader($inputProcessor, 'norm_string_query'),
            $resourceMetadataFactory,
            $eventDispatcher
        );

        $listener->onKernelRequest(new RequestEvent($httpKernel, $request, HttpKernelInterface::MAIN_REQUEST));

        self::assertOperationAttrEquals(
            [
                '_api_resource_class' => 'dummy',
                '_api_operation_name' => 'get_collection',
                '_api_search_config' => ['fieldset' => BookFieldSet::class],
                '_api_search_context' => 'frontend',
                '_api_search_condition' => SpyingInputProcessor::getCondition(),
            ],
            $request
        );
        self::assertEquals('', $inputProcessor->getInput());

        $config = $inputProcessor->getConfig();
        self::assertEquals(BookFieldSet::class, $config->getFieldSet()->getSetName());
    }

    /** @test */
    public function it_does_nothing_when_no_metadata_is_set(): void
    {
        $httpKernel = $this->createMock(HttpKernelInterface::class);
        $resourceMetadataFactory = $this->createResourceMetadataFactory([]);

        $eventDispatcher = $this->expectingNoCallEventDispatcher();
        $listener = new SearchConditionListener(
            $this->getFactory(),
            $this->createProcessorLoader(new StubInputProcessor(), 'noop'),
            $resourceMetadataFactory,
            $eventDispatcher
        );

        $request = new Request([], [], ['_api_resource_class' => 'dummy', '_api_operation_name' => 'get_collection']);
        $listener->onKernelRequest(new RequestEvent($httpKernel, $request, HttpKernelInterface::MAIN_REQUEST));

        self::assertOperationAttrEquals(['_api_resource_class' => 'dummy', '_api_operation_name' => 'get_collection'], $request);
    }

    /** @test */
    public function it_does_nothing_not_cacheable_requests(): void
    {
        $httpKernel = $this->createMock(HttpKernelInterface::class);

        $resourceMetadataFactory = $this->createResourceMetadataFactory([]);

        $request = new Request([], [], ['_api_resource_class' => 'dummy', '_api_operation_name' => 'get_collection']);
        $request->setMethod('POST');

        $eventDispatcher = $this->expectingNoCallEventDispatcher();
        $listener = new SearchConditionListener(
            $this->getFactory(),
            $this->createProcessorLoader(new StubInputProcessor(), 'noop'),
            $resourceMetadataFactory,
            $eventDispatcher
        );

        $listener->onKernelRequest(new RequestEvent($httpKernel, $request, HttpKernelInterface::MAIN_REQUEST));

        self::assertOperationAttrEquals([
            '_api_resource_class' => 'dummy',
            '_api_operation_name' => 'get_collection',
        ], $request);
    }

    /** @test */
    public function it_maps_configuration_processor_configuration(): void
    {
        $httpKernel = $this->createMock(HttpKernelInterface::class);
        $resourceMetadataFactory = $this->createResourceMetadataFactory([
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
        ]);

        $request = new Request([], [], ['_api_resource_class' => 'dummy', '_api_operation_name' => 'get_collection']);
        $eventDispatcher = $this->expectingCallEventDispatcher($request);

        $inputProcessor = new SpyingInputProcessor();
        $listener = new SearchConditionListener(
            $this->getFactory(),
            $this->createProcessorLoader($inputProcessor, 'norm_string_query'),
            $resourceMetadataFactory,
            $eventDispatcher
        );

        $listener->onKernelRequest(new RequestEvent($httpKernel, $request, HttpKernelInterface::MAIN_REQUEST));

        self::assertOperationAttrEquals(
            [
                '_api_resource_class' => 'dummy',
                '_api_operation_name' => 'get_collection',
                '_api_search_config' => [
                    'fieldset' => BookFieldSet::class,
                    'processor' => ['cache_ttl' => 30],
                ],
                '_api_search_context' => '_any',
                '_api_search_condition' => SpyingInputProcessor::getCondition(),
            ],
            $request
        );

        $config = $inputProcessor->getConfig();
        self::assertEquals(BookFieldSet::class, $config->getFieldSet()->getSetName());
        self::assertEquals(30, $config->getCacheTTL());
    }

    /** @test */
    public function it_stores_cached_result_when_configured(): void
    {
        $httpKernel = $this->createMock(HttpKernelInterface::class);
        $resourceMetadataFactory = $this->createResourceMetadataFactory([
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
        ]);

        $request = new Request([], [], ['_api_resource_class' => 'dummy', '_api_operation_name' => 'get_collection']);
        $eventDispatcher = $this->expectingCallEventDispatcher($request);

        $inputProcessor = new SpyingInputProcessor();
        $listener = new SearchConditionListener(
            $this->getFactory(),
            $this->createProcessorLoader($inputProcessor, 'norm_string_query'),
            $resourceMetadataFactory,
            $eventDispatcher,
            new Psr16Cache($arrayCache = new ArrayAdapter())
        );

        $listener->onKernelRequest(new RequestEvent($httpKernel, $request, HttpKernelInterface::MAIN_REQUEST));

        self::assertOperationAttrEquals([
            '_api_resource_class' => 'dummy',
            '_api_operation_name' => 'get_collection',
            '_api_search_config' => [
                'fieldset' => BookFieldSet::class,
                'processor' => ['cache_ttl' => 30],
            ],
            '_api_search_context' => '_any',
            '_api_search_condition' => SpyingInputProcessor::getCondition(),
        ], $request);
        self::assertCount(1, $arrayCache->getValues());

        $config = $inputProcessor->getConfig();
        self::assertEquals(BookFieldSet::class, $config->getFieldSet()->getSetName());
        self::assertEquals(30, $config->getCacheTTL());
    }

    /** @test */
    public function it_does_not_store_cached_result_when_ttl_is_not_configured(): void
    {
        $httpKernel = $this->createMock(HttpKernelInterface::class);
        $resourceMetadataFactory = $this->createResourceMetadataFactory([
            'rollerworks_search' => [
                'contexts' => [
                    '_any' => [
                        'fieldset' => BookFieldSet::class,
                    ],
                ],
            ],
        ]);

        $request = new Request([], [], ['_api_resource_class' => 'dummy', '_api_operation_name' => 'get_collection']);
        $eventDispatcher = $this->expectingCallEventDispatcher($request);

        $inputProcessor = new SpyingInputProcessor();
        $listener = new SearchConditionListener(
            $this->getFactory(),
            $this->createProcessorLoader($inputProcessor, 'norm_string_query'),
            $resourceMetadataFactory,
            $eventDispatcher,
            $cache = new Psr16Cache($arrayCache = new ArrayAdapter())
        );

        $listener->onKernelRequest($event = new RequestEvent($httpKernel, $request, HttpKernelInterface::MAIN_REQUEST));

        self::assertOperationAttrEquals(
            [
                '_api_resource_class' => 'dummy',
                '_api_operation_name' => 'get_collection',
                '_api_search_config' => [
                    'fieldset' => BookFieldSet::class,
                ],
                '_api_search_context' => '_any',
                '_api_search_condition' => SpyingInputProcessor::getCondition(),
            ],
            $request
        );
        self::assertCount(0, $arrayCache->getValues());

        $config = $inputProcessor->getConfig();
        self::assertEquals(BookFieldSet::class, $config->getFieldSet()->getSetName());
        self::assertNull($config->getCacheTTL());
    }

    /**
     * @test
     *
     * @dataProvider provideIt_errors_when_context_configuration_is_invalidCases
     */
    public function it_errors_when_context_configuration_is_invalid(string $message, array $config): void
    {
        $httpKernel = $this->createMock(HttpKernelInterface::class);
        $resourceMetadataFactory = $this->createResourceMetadataFactory($config);

        $request = new Request(
            ['search' => ['fields' => ['id' => ['single-values' => [1, 1]]]]],
            [],
            ['_api_resource_class' => 'dummy', '_api_operation_name' => 'get_collection']
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

        $listener->onKernelRequest($event = new RequestEvent($httpKernel, $request, HttpKernelInterface::MAIN_REQUEST));
    }

    public static function provideIt_errors_when_context_configuration_is_invalidCases(): iterable
    {
        $resourceClass = 'dummy';

        return [
            'context with missing fieldset' => [
                'Search context "_any" is incorrectly configured for Resource "' . $resourceClass . '#attributes[rollerworks_search]", missing a "fieldset" reference.',
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
                'Processor option "foo" is not supported for Resource "' . $resourceClass . '".',
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
                'Search context "_any" is not supported for Resource "' . $resourceClass . '#attributes[rollerworks_search][contexts]", supported: "foo", "bar".',
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
                'Resource "' . $resourceClass . '#attributes[rollerworks_search]" is missing a contexts array. Add a "contexts" array with at least one entry.',
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

    private function expectingCallEventDispatcher(Request $request, string $resourceClass = 'dummy'): EventDispatcherInterface
    {
        $event = new SearchConditionEvent(SpyingInputProcessor::getCondition(), $resourceClass, $request);

        $eventDispatcherProphecy = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcherProphecy->dispatch($event, SearchConditionEvent::SEARCH_CONDITION_EVENT)->willReturnArgument()->shouldBeCalled();
        $eventDispatcherProphecy->dispatch($event, SearchConditionEvent::SEARCH_CONDITION_EVENT . $resourceClass)->willReturnArgument()->shouldBeCalled();

        return $eventDispatcherProphecy->reveal();
    }

    private function expectingNoCallEventDispatcher(string $resourceClass = 'dummy'): EventDispatcherInterface
    {
        $eventDispatcherProphecy = $this->prophesize(EventDispatcherInterface::class);
        $eventDispatcherProphecy->dispatch(Argument::any(), SearchConditionEvent::SEARCH_CONDITION_EVENT)->willReturnArgument()->shouldNotBeCalled();
        $eventDispatcherProphecy->dispatch(Argument::any(), SearchConditionEvent::SEARCH_CONDITION_EVENT . $resourceClass)->willReturnArgument()->shouldNotBeCalled();

        return $eventDispatcherProphecy->reveal();
    }

    private function createProcessorLoader(InputProcessor $inputProcessor, string $name): InputProcessorLoader
    {
        return new InputProcessorLoader(
            new ClosureContainer(
                [
                    $name => static fn () => $inputProcessor,
                ]
            ),
            [$name => $name]
        );
    }

    private static function assertOperationAttrEquals(array $attributes, Request $request): void
    {
        $requestAttributes = $request->attributes->all();

        // This object graph is to big for comparison and is considered an internal detail
        unset($attributes['_api_operation'], $requestAttributes['_api_operation']);

        self::assertEquals($attributes, $requestAttributes);
    }
}
