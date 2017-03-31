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

namespace Rollerworks\Component\Search\ApiPlatform\Tests\Doctrine\Orm\Extension;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use ApiPlatform\Core\Metadata\Resource\ResourceMetadata;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\Dummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy;
use ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\ThirdLevel;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Rollerworks\Component\Search\ApiPlatform\Doctrine\Orm\Extension\SearchExtension;
use Rollerworks\Component\Search\ApiPlatform\Doctrine\Orm\QueryBuilder;
use Rollerworks\Component\Search\Doctrine\Orm\CachedDqlConditionGenerator;
use Rollerworks\Component\Search\Doctrine\Orm\DoctrineOrmFactory;
use Rollerworks\Component\Search\Doctrine\Orm\DqlConditionGenerator;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\Value\ValuesGroup;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/** @internal */
class SearchExtensionTest extends TestCase
{
    public function testApplyToCollectionWithValidCondition()
    {
        $searchCondition = $this->createCondition();
        $dummyMetadata = new ResourceMetadata(
            'dummy',
            'dummy',
            '#dummy',
            [],
            [],
            [
                'rollerworks_search' => [
                    'doctrine_orm' => [
                        'dummy_fieldset' => [
                            'mappings' => [
                                'dummy-id' => 'id',
                                'dummy-name' => ['property' => 'name'],
                            ],
                        ],
                    ],
                ],
            ]
        );

        $resourceMetadataFactory = $this->createResourceMetadata($dummyMetadata);
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilder = $queryBuilderProphecy->reveal();

        $conditionGeneratorProphecy = $this->prophesize(DqlConditionGenerator::class);
        $conditionGenerator = $conditionGeneratorProphecy->reveal();

        $cachedConditionGeneratorProphecy = $this->prophesize(CachedDqlConditionGenerator::class);
        $cachedConditionGeneratorProphecy->setField('dummy-id', 'id', 'o', Dummy::class, null)->shouldBeCalled();
        $cachedConditionGeneratorProphecy->setField('dummy-name', 'name', 'o', Dummy::class, null)->shouldBeCalled();
        $cachedConditionGeneratorProphecy->updateQuery()->shouldBeCalled();

        $ormFactoryProphecy = $this->prophesize(DoctrineOrmFactory::class);
        $ormFactoryProphecy->createConditionGenerator($queryBuilder, $searchCondition)->willReturn($conditionGenerator);
        $ormFactoryProphecy->createCachedConditionGenerator($conditionGenerator)->willReturn($cachedConditionGeneratorProphecy->reveal());

        $request = new Request([], [], ['_api_search_condition' => $searchCondition]);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $orderExtensionTest = new SearchExtension($requestStack, $resourceMetadataFactory, $ormFactoryProphecy->reveal());
        $orderExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), Dummy::class, 'get');
    }

    public function testApplyToCollectionWithValidConditionForDoctrineWildcard()
    {
        $searchCondition = $this->createCondition();
        $dummyMetadata = new ResourceMetadata(
            'dummy',
            'dummy',
            '#dummy',
            [],
            [],
            [
                'rollerworks_search' => [
                    'doctrine_orm' => [
                        '*' => [
                            'mappings' => [
                                'dummy-id' => 'id',
                                'dummy-name' => 'name',
                            ],
                        ],
                    ],
                ],
            ]
        );

        $resourceMetadataFactory = $this->createResourceMetadata($dummyMetadata);
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilder = $queryBuilderProphecy->reveal();

        $conditionGeneratorProphecy = $this->prophesize(DqlConditionGenerator::class);
        $conditionGenerator = $conditionGeneratorProphecy->reveal();

        $cachedConditionGeneratorProphecy = $this->prophesize(CachedDqlConditionGenerator::class);
        $cachedConditionGeneratorProphecy->setField('dummy-id', 'id', 'o', Dummy::class, null)->shouldBeCalled();
        $cachedConditionGeneratorProphecy->setField('dummy-name', 'name', 'o', Dummy::class, null)->shouldBeCalled();
        $cachedConditionGeneratorProphecy->updateQuery()->shouldBeCalled();

        $ormFactoryProphecy = $this->prophesize(DoctrineOrmFactory::class);
        $ormFactoryProphecy->createConditionGenerator($queryBuilder, $searchCondition)->willReturn($conditionGenerator);
        $ormFactoryProphecy->createCachedConditionGenerator($conditionGenerator)->willReturn($cachedConditionGeneratorProphecy->reveal());

        $request = new Request([], [], ['_api_search_condition' => $searchCondition]);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $orderExtensionTest = new SearchExtension($requestStack, $resourceMetadataFactory, $ormFactoryProphecy->reveal());
        $orderExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), Dummy::class, 'get');
    }

    public function testApplyToCollectionWithValidConditionUseSetNameBeforeWildcard()
    {
        $searchCondition = $this->createCondition();
        $dummyMetadata = new ResourceMetadata(
            'dummy',
            'dummy',
            '#dummy',
            [],
            [],
            [
                'rollerworks_search' => [
                    'doctrine_orm' => [
                        '*' => [
                            'mappings' => [
                                'dummy-id' => 'id',
                                'dummy-alias' => 'alias',
                            ],
                        ],
                        'dummy_fieldset' => [
                            'mappings' => [
                                'dummy-id' => 'id',
                                'dummy-name' => 'name',
                            ],
                        ],
                    ],
                ],
            ]
        );

        $resourceMetadataFactory = $this->createResourceMetadata($dummyMetadata);
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilder = $queryBuilderProphecy->reveal();

        $conditionGeneratorProphecy = $this->prophesize(DqlConditionGenerator::class);
        $conditionGenerator = $conditionGeneratorProphecy->reveal();

        $cachedConditionGeneratorProphecy = $this->prophesize(CachedDqlConditionGenerator::class);
        $cachedConditionGeneratorProphecy->setField('dummy-id', 'id', 'o', Dummy::class, null)->shouldBeCalled();
        $cachedConditionGeneratorProphecy->setField('dummy-name', 'name', 'o', Dummy::class, null)->shouldBeCalled();
        $cachedConditionGeneratorProphecy->updateQuery()->shouldBeCalled();

        $ormFactoryProphecy = $this->prophesize(DoctrineOrmFactory::class);
        $ormFactoryProphecy->createConditionGenerator($queryBuilder, $searchCondition)->willReturn($conditionGenerator);
        $ormFactoryProphecy->createCachedConditionGenerator($conditionGenerator)->willReturn($cachedConditionGeneratorProphecy->reveal());

        $request = new Request([], [], ['_api_search_condition' => $searchCondition]);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $orderExtensionTest = new SearchExtension($requestStack, $resourceMetadataFactory, $ormFactoryProphecy->reveal());
        $orderExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), Dummy::class, 'get');
    }

    public function testApplyToCollectionWithRelations()
    {
        $searchCondition = $this->createCondition();
        $dummyMetadata = new ResourceMetadata(
            'dummy_car',
            'dummy_car',
            '#dummy_car',
            [],
            [],
            [
                'rollerworks_search' => [
                    'doctrine_orm' => [
                        'dummy_fieldset' => [
                            'mappings' => [
                                'dummy-id' => 'id',
                                'dummy-name' => 'name',
                                'fiend-name' => ['property' => 'name', 'alias' => 'r'],
                                'level' => ['property' => 'level', 'alias' => 't', 'type' => 'integer'],
                            ],
                            'relations' => [
                                'r' => ['join' => 'o.relatedToDummyFriend', 'entity' => RelatedDummy::class],
                                't' => [
                                    'join' => 'o.thirdLevel',
                                    'entity' => ThirdLevel::class,
                                    'conditionType' => 'WITH',
                                    'condition' => 't.id = o.id',
                                    'index' => 'o.id',
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );

        $resourceMetadataFactory = $this->createResourceMetadata($dummyMetadata, RelatedDummy::class);
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->leftJoin('o.relatedToDummyFriend', 'r', null, null, null)->shouldBeCalled();
        $queryBuilderProphecy->leftJoin('o.thirdLevel', 't', 'WITH', 't.id = o.id', 'o.id')->shouldBeCalled();
        $queryBuilder = $queryBuilderProphecy->reveal();

        $conditionGeneratorProphecy = $this->prophesize(DqlConditionGenerator::class);
        $conditionGenerator = $conditionGeneratorProphecy->reveal();

        $cachedConditionGeneratorProphecy = $this->prophesize(CachedDqlConditionGenerator::class);
        $cachedConditionGeneratorProphecy->setField('dummy-id', 'id', 'o', RelatedDummy::class, null)->shouldBeCalled();
        $cachedConditionGeneratorProphecy->setField('dummy-name', 'name', 'o', RelatedDummy::class, null)->shouldBeCalled();
        $cachedConditionGeneratorProphecy->setField('fiend-name', 'name', 'r', RelatedDummy::class, null)->shouldBeCalled();
        $cachedConditionGeneratorProphecy->setField('level', 'level', 't', ThirdLevel::class, 'integer')->shouldBeCalled();

        $cachedConditionGeneratorProphecy->updateQuery()->shouldBeCalled();

        $ormFactoryProphecy = $this->prophesize(DoctrineOrmFactory::class);
        $ormFactoryProphecy->createConditionGenerator($queryBuilder, $searchCondition)->willReturn($conditionGenerator);
        $ormFactoryProphecy->createCachedConditionGenerator($conditionGenerator)->willReturn($cachedConditionGeneratorProphecy->reveal());

        $request = new Request([], [], ['_api_search_condition' => $searchCondition]);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $orderExtensionTest = new SearchExtension($requestStack, $resourceMetadataFactory, $ormFactoryProphecy->reveal());
        $orderExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), RelatedDummy::class, 'get');
    }

    /**
     * @dataProvider provideInvalidConfigurations
     */
    public function testApplyToCollectionGivesExceptionWhenConfigIsInValid(string $message, $config)
    {
        $searchCondition = $this->createCondition();
        $dummyMetadata = new ResourceMetadata(
            'dummy_car',
            'dummy_car',
            '#dummy_car',
            [],
            [],
            ['rollerworks_search' => ['doctrine_orm' => $config]]
        );

        $resourceMetadataFactory = $this->createResourceMetadata($dummyMetadata, RelatedDummy::class);
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilder = $queryBuilderProphecy->reveal();

        $conditionGeneratorProphecy = $this->prophesize(DqlConditionGenerator::class);
        $conditionGenerator = $conditionGeneratorProphecy->reveal();
        $cachedConditionGeneratorProphecy = $this->prophesize(CachedDqlConditionGenerator::class);

        $ormFactoryProphecy = $this->prophesize(DoctrineOrmFactory::class);
        $ormFactoryProphecy->createConditionGenerator($queryBuilder, $searchCondition)->willReturn($conditionGenerator);
        $ormFactoryProphecy->createCachedConditionGenerator($conditionGenerator)->willReturn($cachedConditionGeneratorProphecy->reveal());

        $request = new Request([], [], ['_api_search_condition' => $searchCondition]);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $orderExtensionTest = new SearchExtension($requestStack, $resourceMetadataFactory, $ormFactoryProphecy->reveal());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($message);

        $orderExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), RelatedDummy::class, 'get');
    }

    public function provideInvalidConfigurations(): array
    {
        return [
            [
                'Config "ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy#attributes:rollerworks_search.doctrine_orm[dummy_fieldset][relations][r]" is missing "entity", got "join".',
                [
                    'dummy_fieldset' => [
                        'mappings' => [
                            'dummy-id' => 'id',
                        ],
                        'relations' => [
                            'r' => ['join' => 'o.relatedToDummyFriend'],
                        ],
                    ],
                ],
            ],
            [
                'Config "ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy#attributes:rollerworks_search.doctrine_orm[dummy_fieldset][mappings][fiend-name]" accepts only "property", "alias", "type", got "field", "alias".',
                [
                    'dummy_fieldset' => [
                        'mappings' => [
                            'dummy-id' => 'id',
                            'fiend-name' => ['field' => 'name', 'alias' => 'r'],
                        ],
                    ],
                ],
            ],
            [
                'Config "ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy#attributes:rollerworks_search.doctrine_orm[dummy_fieldset][mappings][fiend-name]" is missing "property", got "alias".',
                [
                    'dummy_fieldset' => [
                        'mappings' => [
                            'dummy-id' => 'id',
                            'fiend-name' => ['alias' => 'r'],
                        ],
                        'relations' => [
                            'r' => ['join' => 'o.relatedToDummyFriend', 'entity' => RelatedDummy::class],
                        ],
                    ],
                ],
            ],
            [
                'Invalid value for "ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy#attributes:rollerworks_search.doctrine_orm[dummy_fieldset][mappings][fiend-name][alias]", alias "r" is not registered in the "relations".',
                [
                    'dummy_fieldset' => [
                        'mappings' => [
                            'fiend-name' => ['property' => 'name', 'alias' => 'r'],
                        ],
                    ],
                ],
            ],
            [
                'Invalid configuration for "ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy#attributes:rollerworks_search.doctrine_orm[dummy_fieldset][relations]", relation name "o" is already used for the root.',
                [
                    'dummy_fieldset' => [
                        'mappings' => [
                            'dummy-id' => 'id',
                            'fiend-name' => ['field' => 'name', 'alias' => 'r'],
                        ],
                        'relations' => [
                            'o' => ['join' => 'o.relatedToDummyFriend'],
                        ],
                    ],
                ],
            ],
            [
                'ApiPlatform\Core\Tests\Fixtures\TestBundle\Entity\RelatedDummy#attributes:rollerworks_search.doctrine_orm[dummy_fieldset][relations][r][type]", type "outer" is not supported. Use left, right or inner.',
                [
                    'dummy_fieldset' => [
                        'mappings' => [
                            'dummy-id' => 'id',
                            'fiend-name' => ['field' => 'name', 'alias' => 'r'],
                        ],
                        'relations' => [
                            'r' => ['join' => 'o.relatedToDummyFriend', 'entity' => RelatedDummy::class, 'type' => 'outer'],
                        ],
                    ],
                ],
            ],
        ];
    }

    public function testApplyToCollectionWithoutCondition()
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create(Dummy::class)->shouldBeCalled()->shouldNotBeCalled();
        $resourceMetadataFactory = $resourceMetadataFactoryProphecy->reveal();

        $ormFactoryProphecy = $this->prophesize(DoctrineOrmFactory::class);
        $ormFactoryProphecy->createCachedConditionGenerator(Argument::any())->shouldNotBeCalled();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilder = $queryBuilderProphecy->reveal();

        $request = new Request();
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $orderExtensionTest = new SearchExtension($requestStack, $resourceMetadataFactory, $ormFactoryProphecy->reveal());
        $orderExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), Dummy::class, 'get');
    }

    public function testApplyToCollectionNoResourceMetadataForSearch()
    {
        $searchCondition = $this->createCondition();
        $dummyMetadata = new ResourceMetadata('dummy', 'dummy', '#dummy');

        $resourceMetadataFactory = $this->createResourceMetadata($dummyMetadata);

        $ormFactoryProphecy = $this->prophesize(DoctrineOrmFactory::class);
        $ormFactoryProphecy->createCachedConditionGenerator(Argument::any())->shouldNotBeCalled();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilder = $queryBuilderProphecy->reveal();

        $request = new Request([], [], ['_api_search_condition' => $searchCondition]);
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $orderExtensionTest = new SearchExtension($requestStack, $resourceMetadataFactory, $ormFactoryProphecy->reveal());
        $orderExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), Dummy::class, 'get');
    }

    private function createResourceMetadata(ResourceMetadata $metadata = null, string $resourceClass = Dummy::class): ResourceMetadataFactoryInterface
    {
        $resourceMetadataFactoryProphecy = $this->prophesize(ResourceMetadataFactoryInterface::class);
        $resourceMetadataFactoryProphecy->create($resourceClass)->shouldBeCalled()->willReturn($metadata);

        return $resourceMetadataFactoryProphecy->reveal();
    }

    private function createCondition(?string $setName = 'dummy_fieldset'): SearchCondition
    {
        $fieldSet = $this->prophesize(FieldSet::class);
        $fieldSet->getSetName()->willReturn($setName);

        return new SearchCondition($fieldSet->reveal(), new ValuesGroup());
    }
}
