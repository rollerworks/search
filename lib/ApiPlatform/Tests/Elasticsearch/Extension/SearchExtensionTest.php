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

namespace Rollerworks\Component\Search\ApiPlatform\Tests\Elasticsearch\Extension;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGenerator;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\ClassMetadata;
use Doctrine\Persistence\ObjectManager;
use Elastica\Client;
use Elastica\Query;
use Elastica\Response;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Rollerworks\Component\Search\ApiPlatform\Elasticsearch\Extension\SearchExtension;
use Rollerworks\Component\Search\ApiPlatform\Tests\Fixtures\Dummy;
use Rollerworks\Component\Search\Elasticsearch\CachedConditionGenerator;
use Rollerworks\Component\Search\Elasticsearch\ElasticsearchFactory;
use Rollerworks\Component\Search\Elasticsearch\QueryConditionGenerator;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\Value\ValuesGroup;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/** @internal */
final class SearchExtensionTest extends TestCase
{
    /** @test */
    public function apply_to_collection_with_valid_condition(): void
    {
        $query = new Query(['query' => ['bool' => ['must' => 'foo']]]);
        $ids = [3, 1, 5];

        $elasticaResponse = $this->createResponse($ids);
        $searchCondition = $this->createCondition();

        $queryFunctionProphecy = $this->prophesize(Expr\Func::class);
        $queryFunction = $queryFunctionProphecy->reveal();

        $queryExpressionProphecy = $this->prophesize(Expr::class);
        $queryExpressionProphecy->in('o.id', ':ids')->willReturn($queryFunction);
        $queryExpression = $queryExpressionProphecy->reveal();

        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphecy->andWhere($queryFunction)->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->expr()->willReturn($queryExpression);
        $queryBuilderProphecy->setParameter('ids', $ids)->shouldBeCalled();
        $queryBuilderProphecy->getFirstResult()->shouldBeCalled();
        $queryBuilderProphecy->getMaxResults()->shouldBeCalled();
        $queryBuilderProphecy->getRootAliases()->willReturn(['o']);
        $queryBuilderProphecy->setParameter('id0', 3)->shouldBeCalled();
        $queryBuilderProphecy->setParameter('id1', 1)->shouldBeCalled();
        $queryBuilderProphecy->setParameter('id2', 5)->shouldBeCalled();
        $queryBuilderProphecy->addSelect('CASE WHEN o.id = :id0 THEN 0 WHEN o.id = :id1 THEN 1 WHEN o.id = :id2 THEN 2 ELSE 3 END AS HIDDEN order_by')->willReturn($queryBuilderProphecy);
        $queryBuilderProphecy->orderBy('order_by', 'ASC')->shouldBeCalled();
        $queryBuilder = $queryBuilderProphecy->reveal();

        $classMetadataProphecy = $this->prophesize(ClassMetadata::class);
        $classMetadataProphecy->getIdentifier()->willReturn(['id']);
        $classMetadata = $classMetadataProphecy->reveal();

        $managerProphecy = $this->prophesize(ObjectManager::class);
        $managerProphecy->getClassMetadata(Dummy::class)->willReturn($classMetadata);
        $manager = $managerProphecy->reveal();

        $managerRegistryProphecy = $this->prophesize(ManagerRegistry::class);
        $managerRegistryProphecy->getManagerForClass(Dummy::class)->willReturn($manager);
        $managerRegistry = $managerRegistryProphecy->reveal();

        $elasticaClientProphecy = $this->prophesize(Client::class);
        $elasticaClientProphecy->request('/_search', Argument::in(['POST', 'GET']), $query->toArray(), [])->willReturn($elasticaResponse)->shouldBeCalled();
        $elasticaClient = $elasticaClientProphecy->reveal();

        $conditionGeneratorProphecy = $this->prophesize(QueryConditionGenerator::class);
        $conditionGenerator = $conditionGeneratorProphecy->reveal();

        $cachedConditionGeneratorProphecy = $this->prophesize(CachedConditionGenerator::class);
        $cachedConditionGeneratorProphecy->registerField('dummy-id', 'id', [], [])->shouldBeCalled();
        $cachedConditionGeneratorProphecy->registerField('dummy-name', 'name', [], [])->shouldBeCalled();
        $cachedConditionGeneratorProphecy->getQuery()->willReturn($query);
        $cachedConditionGeneratorProphecy->getMappings()->shouldBeCalled();
        $cachedConditionGenerator = $cachedConditionGeneratorProphecy->reveal();

        $elasticsearchFactoryProphecy = $this->prophesize(ElasticsearchFactory::class);
        $elasticsearchFactoryProphecy->createConditionGenerator($searchCondition)->willReturn($conditionGenerator);
        $elasticsearchFactoryProphecy->createCachedConditionGenerator($conditionGenerator)->willReturn($cachedConditionGenerator);
        $elasticsearchFactory = $elasticsearchFactoryProphecy->reveal();

        $request = new Request([], [], [
            '_api_search_condition' => $searchCondition,
            '_api_search_context' => 'dummy',
            '_api_search_config' => [
                'elasticsearch' => [
                    'mappings' => [
                        'dummy-id' => 'id',
                        'dummy-name' => 'name',
                    ],
                ],
            ],
        ]);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        $orderExtensionTest = new SearchExtension($requestStack, $managerRegistry, $elasticsearchFactory, $elasticaClient);
        $orderExtensionTest->applyToCollection($queryBuilder, new QueryNameGenerator(), Dummy::class, 'get');
    }

    private function createCondition(?string $setName = 'dummy_fieldset'): SearchCondition
    {
        $fieldSetProphecy = $this->prophesize(FieldSet::class);
        $fieldSetProphecy->getSetName()->willReturn($setName);

        return new SearchCondition($fieldSetProphecy->reveal(), new ValuesGroup());
    }

    private function createResponse($ids): Response
    {
        $response = [];

        foreach ($ids as $id) {
            $response['hits']['hits'][]['_id'] = $id;
        }

        return new Response($response);
    }
}
