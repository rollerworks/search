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
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Elastica\Client;
use Elastica\Response;
use PHPUnit\Framework\TestCase;
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
class SearchExtensionTest extends TestCase
{
    public function testApplyToCollectionWithValidCondition()
    {
        $query = ['query' => ['bool' => ['must' => 'foo']]];
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
        $elasticaClientProphecy->request('/_search', 'GET', $query, [])->willReturn($elasticaResponse);
        $elasticaClient = $elasticaClientProphecy->reveal();

        $conditionGeneratorProphecy = $this->prophesize(QueryConditionGenerator::class);
        $conditionGenerator = $conditionGeneratorProphecy->reveal();

        $cachedConditionGeneratorProphecy = $this->prophesize(CachedConditionGenerator::class);
        $cachedConditionGeneratorProphecy->registerField('dummy-id', 'id')->shouldBeCalled();
        $cachedConditionGeneratorProphecy->registerField('dummy-name', 'name')->shouldBeCalled();
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
