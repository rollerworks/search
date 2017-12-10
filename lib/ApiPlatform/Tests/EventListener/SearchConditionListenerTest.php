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

use ApiPlatform\Core\Api\UrlGeneratorInterface;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use Prophecy\Argument;
use Rollerworks\Component\Search\ApiPlatform\EventListener\SearchConditionListener;
use Rollerworks\Component\Search\ApiPlatform\SearchConditionEvent;
use Rollerworks\Component\Search\ApiPlatform\Tests\Fixtures\BookFieldSet;
use Rollerworks\Component\Search\ApiPlatform\Tests\Fixtures\Dummy;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\Processor\ProcessorConfig;
use Rollerworks\Component\Search\Processor\SearchPayload;
use Rollerworks\Component\Search\Processor\SearchProcessor;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\Test\SearchIntegrationTestCase;
use Rollerworks\Component\Search\Value\ValuesGroup;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class SearchConditionListenerTest extends SearchIntegrationTestCase
{
    /** @test */
    public function it_sets_search_condition_and_config()
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

        $searchPayload = new SearchPayload();
        $searchPayload->searchCondition = $condition = $this->createCondition();
        $searchPayload->exportedFormat = 'norm_string_query';
        $searchPayload->exportedCondition = 'id: 1, 2;';

        $processorProphecy = $this->prophesize(SearchProcessor::class);
        $processorProphecy->processRequest($request, Argument::any())->willReturn($searchPayload);
        $searchProcessor = $processorProphecy->reveal();

        $urlGeneratorProphecy = $this->prophesize(UrlGeneratorInterface::class);
        $urlGeneratorProphecy->generate(Argument::any(), Argument::any(), Argument::any())->shouldNotBeCalled();
        $urlGenerator = $urlGeneratorProphecy->reveal();

        $eventDispatcher = $this->expectingCallEventDispatcher($searchPayload, $request);
        $listener = new SearchConditionListener($this->getFactory(), $searchProcessor, $urlGenerator, $resourceMetadataFactory, $eventDispatcher);
        $listener->onKernelRequest($event = new GetResponseEvent($httpKernel, $request, HttpKernelInterface::MASTER_REQUEST));

        self::assertNull($event->getResponse());
        self::assertEquals(
            [
                '_api_resource_class' => Dummy::class,
                '_api_search_config' => ['fieldset' => BookFieldSet::class],
                '_api_search_context' => '_any',
                '_api_search_condition' => $condition,
            ],
            $request->attributes->all()
        );
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

        $searchPayload = new SearchPayload();
        $searchPayload->searchCondition = $condition = $this->createCondition();
        $searchPayload->exportedFormat = 'norm_string_query';
        $searchPayload->exportedCondition = 'id: 1, 2;';

        $processorProphecy = $this->prophesize(SearchProcessor::class);
        $processorProphecy->processRequest($request, Argument::any())->willReturn($searchPayload);
        $searchProcessor = $processorProphecy->reveal();

        $urlGeneratorProphecy = $this->prophesize(UrlGeneratorInterface::class);
        $urlGeneratorProphecy->generate(Argument::any(), Argument::any(), Argument::any())->shouldNotBeCalled();
        $urlGenerator = $urlGeneratorProphecy->reveal();

        $eventDispatcher = $this->expectingCallEventDispatcher($searchPayload, $request);
        $listener = new SearchConditionListener($this->getFactory(), $searchProcessor, $urlGenerator, $resourceMetadataFactory, $eventDispatcher);
        $listener->onKernelRequest($event = new GetResponseEvent($httpKernel, $request, HttpKernelInterface::MASTER_REQUEST));

        self::assertNull($event->getResponse());
        self::assertEquals(
            [
                '_api_resource_class' => Dummy::class,
                '_api_search_config' => ['fieldset' => BookFieldSet::class],
                '_api_search_context' => 'frontend',
                '_api_search_condition' => $condition,
            ],
            $request->attributes->all()
        );
    }

    /** @test */
    public function it_sets_a_redirect_when_search_condition_has_changed()
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

        $request = new Request(
            ['search' => ['fields' => ['id' => ['single-values' => [1, 1]]]]],
            [],
            ['_api_resource_class' => Dummy::class, '_route' => 'api_books_collection']
        );

        $searchPayload = new SearchPayload(true);
        $searchPayload->searchCondition = $condition = $this->createCondition();
        $searchPayload->exportedFormat = 'norm_string_query';
        $searchPayload->exportedCondition = ['fields' => ['id' => ['single-values' => [1]]]];

        $processorProphecy = $this->prophesize(SearchProcessor::class);
        $processorProphecy->processRequest($request, Argument::any())->willReturn($searchPayload);
        $searchProcessor = $processorProphecy->reveal();

        $urlGeneratorProphecy = $this->prophesize(UrlGeneratorInterface::class);
        $urlGeneratorProphecy->generate(
            'api_books_collection',
            ['search' => ['fields' => ['id' => ['single-values' => [1]]]]],
            Argument::any()
        )->willReturn('/books?search=id: 1;');

        $urlGenerator = $urlGeneratorProphecy->reveal();

        $eventDispatcher = $this->expectingNoCallEventDispatcher();
        $listener = new SearchConditionListener($this->getFactory(), $searchProcessor, $urlGenerator, $resourceMetadataFactory, $eventDispatcher);
        $listener->onKernelRequest($event = new GetResponseEvent($httpKernel, $request, HttpKernelInterface::MASTER_REQUEST));

        self::assertEquals(new RedirectResponse('/books?search=id: 1;'), $event->getResponse());
        self::assertEquals(
            [
                '_api_resource_class' => Dummy::class,
                '_api_search_config' => ['fieldset' => BookFieldSet::class],
                '_api_search_context' => '_any',
                '_route' => 'api_books_collection',
            ],
            $request->attributes->all()
        );
    }

    /** @test */
    public function it_sets_a_redirect_with_format_when_search_condition_has_changed()
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

        $request = new Request(
            ['search' => ['fields' => ['id' => ['single-values' => [1, 1]]]]],
            [],
            [
                '_api_resource_class' => Dummy::class,
                '_format' => 'json',
                '_route' => 'api_books_collection',
                '_route_params' => [
                    '_api_resource_class' => Dummy::class,
                    '_format' => 'json',
                ],
            ]
        );

        $searchPayload = new SearchPayload(true);
        $searchPayload->searchCondition = $condition = $this->createCondition();
        $searchPayload->exportedFormat = 'norm_string_query';
        $searchPayload->exportedCondition = 'id: 1;';

        $processorProphecy = $this->prophesize(SearchProcessor::class);
        $processorProphecy->processRequest($request, Argument::any())->willReturn($searchPayload);
        $searchProcessor = $processorProphecy->reveal();

        $urlGeneratorProphecy = $this->prophesize(UrlGeneratorInterface::class);
        $urlGeneratorProphecy->generate(
            'api_books_collection',
            ['_format' => 'json', 'search' => 'id: 1;'],
            Argument::any()
        )->willReturn('/books.json?search=id: 1;');

        $urlGenerator = $urlGeneratorProphecy->reveal();

        $eventDispatcher = $this->expectingNoCallEventDispatcher();
        $listener = new SearchConditionListener($this->getFactory(), $searchProcessor, $urlGenerator, $resourceMetadataFactory, $eventDispatcher);
        $listener->onKernelRequest($event = new GetResponseEvent($httpKernel, $request, HttpKernelInterface::MASTER_REQUEST));

        self::assertEquals(new RedirectResponse('/books.json?search=id: 1;'), $event->getResponse());
        self::assertEquals(
            [
                '_api_resource_class' => Dummy::class,
                '_api_search_config' => ['fieldset' => BookFieldSet::class],
                '_api_search_context' => '_any',
                '_format' => 'json',
                '_route' => 'api_books_collection',
                '_route_params' => [
                    '_api_resource_class' => Dummy::class,
                    '_format' => 'json',
                ],
            ],
            $request->attributes->all()
        );
    }

    /** @test */
    public function it_does_nothing_when_no_metadata_is_set()
    {
        $dummyMetadata = new ResourceMetadata('dummy', 'dummy', '#dummy');

        $httpKernel = $this->createMock(HttpKernelInterface::class);
        $resourceMetadataFactory = $this->createResourceMetadata($dummyMetadata);

        $request = new Request([], [], ['_api_resource_class' => Dummy::class]);

        $processorProphecy = $this->prophesize(SearchProcessor::class);
        $processorProphecy->processRequest(Argument::any(), Argument::any())->shouldNotBeCalled();
        $searchProcessor = $processorProphecy->reveal();

        $urlGeneratorProphecy = $this->prophesize(UrlGeneratorInterface::class);
        $urlGeneratorProphecy->generate(Argument::any(), Argument::any(), Argument::any())->shouldNotBeCalled();
        $urlGenerator = $urlGeneratorProphecy->reveal();

        $eventDispatcher = $this->expectingNoCallEventDispatcher();
        $listener = new SearchConditionListener($this->getFactory(), $searchProcessor, $urlGenerator, $resourceMetadataFactory, $eventDispatcher);
        $listener->onKernelRequest($event = new GetResponseEvent($httpKernel, $request, HttpKernelInterface::MASTER_REQUEST));

        self::assertNull($event->getResponse());
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

        $processorProphecy = $this->prophesize(SearchProcessor::class);
        $processorProphecy->processRequest(Argument::any(), Argument::any())->shouldNotBeCalled();
        $searchProcessor = $processorProphecy->reveal();

        $urlGeneratorProphecy = $this->prophesize(UrlGeneratorInterface::class);
        $urlGeneratorProphecy->generate(Argument::any(), Argument::any(), Argument::any())->shouldNotBeCalled();
        $urlGenerator = $urlGeneratorProphecy->reveal();

        $eventDispatcher = $this->expectingNoCallEventDispatcher();
        $listener = new SearchConditionListener($this->getFactory(), $searchProcessor, $urlGenerator, $resourceMetadataFactory, $eventDispatcher);
        $listener->onKernelRequest($event = new GetResponseEvent($httpKernel, $request, HttpKernelInterface::MASTER_REQUEST));

        self::assertNull($event->getResponse());
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
                                'cache_ttl' => '30',
                                'ExportFormat' => 'json',
                            ],
                        ],
                    ],
                ],
            ]
        );

        $httpKernel = $this->createMock(HttpKernelInterface::class);
        $resourceMetadataFactory = $this->createResourceMetadata($dummyMetadata);

        $request = new Request([], [], ['_api_resource_class' => Dummy::class]);

        $searchPayload = new SearchPayload();
        $searchPayload->searchCondition = $condition = $this->createCondition();
        $searchPayload->exportedFormat = 'norm_string_query';
        $searchPayload->exportedCondition = 'id: 1, 2;';

        $fieldSet = $this->getFactory()->createFieldSet(BookFieldSet::class);
        $processorConfig = new ProcessorConfig($fieldSet, 'norm_string_query');
        $processorConfig->setCacheTTL(30);
        $processorConfig->setExportFormat('json');

        $processorProphecy = $this->prophesize(SearchProcessor::class);
        $processorProphecy->processRequest($request, $processorConfig)->willReturn($searchPayload);
        $searchProcessor = $processorProphecy->reveal();

        $urlGeneratorProphecy = $this->prophesize(UrlGeneratorInterface::class);
        $urlGeneratorProphecy->generate(Argument::any(), Argument::any(), Argument::any())->shouldNotBeCalled();
        $urlGenerator = $urlGeneratorProphecy->reveal();

        $eventDispatcher = $this->expectingCallEventDispatcher($searchPayload, $request);
        $listener = new SearchConditionListener($this->getFactory(), $searchProcessor, $urlGenerator, $resourceMetadataFactory, $eventDispatcher);
        $listener->onKernelRequest($event = new GetResponseEvent($httpKernel, $request, HttpKernelInterface::MASTER_REQUEST));

        self::assertNull($event->getResponse());
        self::assertEquals(
            [
                '_api_resource_class' => Dummy::class,
                '_api_search_config' => [
                    'fieldset' => BookFieldSet::class,
                    'processor' => ['cache_ttl' => '30', 'ExportFormat' => 'json'],
                ],
                '_api_search_context' => '_any',
                '_api_search_condition' => $condition,
            ],
            $request->attributes->all()
        );
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

        $processorProphecy = $this->prophesize(SearchProcessor::class);
        $processorProphecy->processRequest($request, Argument::any())->shouldNotBeCalled();
        $searchProcessor = $processorProphecy->reveal();

        $urlGeneratorProphecy = $this->prophesize(UrlGeneratorInterface::class);
        $urlGeneratorProphecy->generate(Argument::any(), Argument::any(), Argument::any())->shouldNotBeCalled();
        $urlGenerator = $urlGeneratorProphecy->reveal();

        $eventDispatcher = $this->expectingNoCallEventDispatcher();
        $listener = new SearchConditionListener($this->getFactory(), $searchProcessor, $urlGenerator, $resourceMetadataFactory, $eventDispatcher);

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

    private function createCondition(?string $setName = BookFieldSet::class): SearchCondition
    {
        $fieldSet = $this->prophesize(FieldSet::class);
        $fieldSet->getSetName()->willReturn($setName);

        return new SearchCondition($fieldSet->reveal(), new ValuesGroup());
    }

    private function expectingCallEventDispatcher(SearchPayload $searchPayload, Request $request): EventDispatcherInterface
    {
        $event = new SearchConditionEvent($searchPayload->searchCondition, Dummy::class, $request);

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
}
