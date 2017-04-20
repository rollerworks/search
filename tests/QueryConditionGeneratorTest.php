<?php

declare(strict_types=1);

namespace Rollerworks\Component\Search\Tests\ElasticSearch;

use Rollerworks\Component\Search\Elasticsearch\QueryConditionGenerator;
use Rollerworks\Component\Search\SearchConditionBuilder;
use Rollerworks\Component\Search\Test\SearchIntegrationTestCase;

class QueryConditionGeneratorTest extends SearchIntegrationTestCase
{
    /** @test */
    public function it_generates_something()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('id')
                ->addSimpleValue(2)
                ->addSimpleValue(5)
            ->end()
            ->field('name')
                ->addSimpleValue('Doctor')
                ->addSimpleValue('Foo')
            ->end()
        ->getSearchCondition();

        $g = new QueryConditionGenerator($condition);

        echo json_encode($g->getQuery()->toArray(), JSON_PRETTY_PRINT);

//        {
//            "bool": {
//                "must": [
//                    {
//                        "terms": {
//                            "id": [2, 5]
//                        }
//                    }
//                ]
//            }
//        }
    }
}
