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

use ApiPlatform\Doctrine\Orm\Util\QueryNameGenerator;
use ApiPlatform\Exception\RuntimeException;
use ApiPlatform\Metadata\Get;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Rollerworks\Component\Search\ApiPlatform\Doctrine\Orm\Extension\SearchExtension;
use Rollerworks\Component\Search\Doctrine\Orm\CachedDqlConditionGenerator;
use Rollerworks\Component\Search\Doctrine\Orm\DoctrineOrmFactory;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\Value\ValuesGroup;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/** @internal */
final class SearchExtensionTest extends TestCase
{
    use ProphecyTrait;

    /** @test */
    public function apply_to_collection_with_valid_condition(): void
    {
        $searchCondition = $this->createCondition();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilder = $queryBuilderProphecy->reveal();

        $cachedConditionGeneratorProphecy = $this->prophesize(CachedDqlConditionGenerator::class);
        $cachedConditionGeneratorProphecy->setField('dummy-id', 'id', 'o', 'dummy', null)->shouldBeCalled();
        $cachedConditionGeneratorProphecy->setField('dummy-name', 'name', 'o', 'dummy', null)->shouldBeCalled();
        $cachedConditionGeneratorProphecy->apply()->shouldBeCalled();

        $ormFactoryProphecy = $this->prophesize(DoctrineOrmFactory::class);
        $ormFactoryProphecy->createCachedConditionGenerator($queryBuilder, $searchCondition)->willReturn($cachedConditionGeneratorProphecy->reveal());

        $request = new Request([], [], [
            '_api_search_condition' => $searchCondition,
            '_api_search_context' => 'dummy',
            '_api_search_config' => [
                'doctrine_orm' => [
                    'mappings' => [
                        'dummy-id' => 'id',
                        'dummy-name' => ['property' => 'name'],
                    ],
                ],
            ],
        ]);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $orderExtensionTest = new SearchExtension($requestStack, $ormFactoryProphecy->reveal());
        $orderExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), 'dummy', new Get(name: 'get'));
    }

    /** @test */
    public function apply_to_collection_with_relations(): void
    {
        $searchCondition = $this->createCondition();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->leftJoin('o.relatedToDummyFriend', 'r', null, null, null)->willReturn($queryBuilderProphecy)->shouldBeCalled();
        $queryBuilderProphecy->leftJoin('o.thirdLevel', 't', 'WITH', 't.id = o.id', 'o.id')->willReturn($queryBuilderProphecy)->shouldBeCalled();
        $queryBuilder = $queryBuilderProphecy->reveal();

        $cachedConditionGeneratorProphecy = $this->prophesize(CachedDqlConditionGenerator::class);
        $cachedConditionGeneratorProphecy->setField('dummy-id', 'id', 'o', 'RelatedDummy', null)->shouldBeCalled();
        $cachedConditionGeneratorProphecy->setField('dummy-name', 'name', 'o', 'RelatedDummy', null)->shouldBeCalled();
        $cachedConditionGeneratorProphecy->setField('fiend-name', 'name', 'r', 'RelatedDummy', null)->shouldBeCalled();
        $cachedConditionGeneratorProphecy->setField('level', 'level', 't', 'ThirdLevel', 'integer')->shouldBeCalled();

        $cachedConditionGeneratorProphecy->apply()->shouldBeCalled();

        $ormFactoryProphecy = $this->prophesize(DoctrineOrmFactory::class);
        $ormFactoryProphecy->createCachedConditionGenerator($queryBuilder, $searchCondition)->willReturn($cachedConditionGeneratorProphecy->reveal());

        $request = new Request([], [], [
            '_api_search_condition' => $searchCondition,
            '_api_search_context' => 'dummy',
            '_api_search_config' => [
                'doctrine_orm' => [
                    'mappings' => [
                        'dummy-id' => 'id',
                        'dummy-name' => 'name',
                        'fiend-name' => ['property' => 'name', 'alias' => 'r'],
                        'level' => ['property' => 'level', 'alias' => 't', 'type' => 'integer'],
                    ],
                    'relations' => [
                        'r' => ['join' => 'o.relatedToDummyFriend', 'entity' => 'RelatedDummy'],
                        't' => [
                            'join' => 'o.thirdLevel',
                            'entity' => 'ThirdLevel',
                            'conditionType' => 'WITH',
                            'condition' => 't.id = o.id',
                            'index' => 'o.id',
                        ],
                    ],
                ],
            ],
        ]);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $orderExtensionTest = new SearchExtension($requestStack, $ormFactoryProphecy->reveal());
        $orderExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), 'RelatedDummy', new Get(name: 'get'));
    }

    /**
     * @dataProvider provideInvalidConfigurations
     *
     * @test
     */
    public function apply_to_collection_gives_exception_when_config_is_in_valid(string $message, array $config): void
    {
        $searchCondition = $this->createCondition();

        $queryBuilder = $this->createMock(QueryBuilder::class);
        $cachedConditionGeneratorProphecy = $this->prophesize(CachedDqlConditionGenerator::class);

        $ormFactoryProphecy = $this->prophesize(DoctrineOrmFactory::class);
        $ormFactoryProphecy->createCachedConditionGenerator($queryBuilder, $searchCondition)->willReturn($cachedConditionGeneratorProphecy->reveal());

        $request = new Request([], [], [
            '_api_search_condition' => $searchCondition,
            '_api_search_context' => 'dummy',
            '_api_search_config' => ['doctrine_orm' => $config],
        ]);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $orderExtensionTest = new SearchExtension($requestStack, $ormFactoryProphecy->reveal());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage($message);

        $orderExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), 'RelatedDummy', new Get(name: 'get'));
    }

    public static function provideInvalidConfigurations(): iterable
    {
        $resourceClass = 'RelatedDummy';

        return [
            [
                'Config "' . $resourceClass . '#attributes[rollerworks_search][contexts][dummy][doctrine_orm][relations][r]" is missing "entity", got "join".',
                [
                    'mappings' => [
                        'dummy-id' => 'id',
                    ],
                    'relations' => [
                        'r' => ['join' => 'o.relatedToDummyFriend'],
                    ],
                ],
            ],
            [
                'Config "' . $resourceClass . '#attributes[rollerworks_search][contexts][dummy][doctrine_orm][mappings][fiend-name]" accepts only "property", "alias", "type", got "field", "alias".',
                [
                    'mappings' => [
                        'dummy-id' => 'id',
                        'fiend-name' => ['field' => 'name', 'alias' => 'r'],
                    ],
                ],
            ],
            [
                'Config "' . $resourceClass . '#attributes[rollerworks_search][contexts][dummy][doctrine_orm][mappings][fiend-name]" is missing "property", got "alias".',
                [
                    'mappings' => [
                        'dummy-id' => 'id',
                        'fiend-name' => ['alias' => 'r'],
                    ],
                    'relations' => [
                        'r' => ['join' => 'o.relatedToDummyFriend', 'entity' => 'RelatedDummy'],
                    ],
                ],
            ],
            [
                'Invalid value for "' . $resourceClass . '#attributes[rollerworks_search][contexts][dummy][doctrine_orm][mappings][fiend-name][alias]", alias "r" is not registered in the "relations".',
                [
                    'mappings' => [
                        'fiend-name' => ['property' => 'name', 'alias' => 'r'],
                    ],
                ],
            ],
            [
                'Invalid configuration for "' . $resourceClass . '#attributes[rollerworks_search][contexts][dummy][doctrine_orm][relations]", relation name "o" is already used for the root.',
                [
                    'mappings' => [
                        'dummy-id' => 'id',
                        'fiend-name' => ['field' => 'name', 'alias' => 'r'],
                    ],
                    'relations' => [
                        'o' => ['join' => 'o.relatedToDummyFriend'],
                    ],
                ],
            ],
            [
                $resourceClass . '#attributes[rollerworks_search][contexts][dummy][doctrine_orm][relations][r][type]", type "outer" is not supported. Use left, right or inner.',
                [
                    'mappings' => [
                        'dummy-id' => 'id',
                        'fiend-name' => ['field' => 'name', 'alias' => 'r'],
                    ],
                    'relations' => [
                        'r' => ['join' => 'o.relatedToDummyFriend', 'entity' => 'RelatedDummy', 'type' => 'outer'],
                    ],
                ],
            ],
        ];
    }

    /** @test */
    public function apply_to_collection_without_condition(): void
    {
        $ormFactoryProphecy = $this->prophesize(DoctrineOrmFactory::class);
        $ormFactoryProphecy->createCachedConditionGenerator(Argument::any(), Argument::any())->shouldNotBeCalled();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilder = $queryBuilderProphecy->reveal();

        $request = new Request();
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $orderExtensionTest = new SearchExtension($requestStack, $ormFactoryProphecy->reveal());
        $orderExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), 'dummy', new Get(name: 'get'));
    }

    private function createCondition(?string $setName = 'dummy_fieldset'): SearchCondition
    {
        $fieldSet = $this->prophesize(FieldSet::class);
        $fieldSet->getSetName()->willReturn($setName);

        return new SearchCondition($fieldSet->reveal(), new ValuesGroup());
    }
}
