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

namespace Rollerworks\Component\Search\Tests\Elasticsearch;

use Rollerworks\Component\Search\Elasticsearch\FieldMapping;
use Rollerworks\Component\Search\Elasticsearch\QueryConditionGenerator;
use Rollerworks\Component\Search\Field\OrderFieldType;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\ParameterBag;
use Rollerworks\Component\Search\SearchConditionBuilder;
use Rollerworks\Component\Search\SearchOrder;
use Rollerworks\Component\Search\SearchPrimaryCondition;
use Rollerworks\Component\Search\Test\SearchIntegrationTestCase;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\ExcludedRange;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\ValuesBag;
use Rollerworks\Component\Search\Value\ValuesGroup;

/**
 * @group unit
 */
final class QueryConditionGeneratorTest extends SearchIntegrationTestCase
{
    /** @test */
    public function it_generates_an_match_all_query_for_empty_condition()
    {
        $condition = $this->createCondition()->getSearchCondition();
        $generator = new QueryConditionGenerator($condition);
        $generator->registerField('id', 'id');
        $generator->registerField('name', 'name');

        self::assertEquals([
            'query' => [
                'match_all' => new \stdClass(),
            ],
        ], $generator->getQuery()->toArray());

        self::assertMapping([], $generator->getMappings());
    }

    /** @test */
    public function it_generates_a_structure_of_root_level_fields()
    {
        $condition = $this->createCondition()
            ->field('id')
                ->addSimpleValue(2)
                ->addSimpleValue(5)
            ->end()
            ->field('name')
                ->addSimpleValue('Doctor')
                ->addSimpleValue('Foo')
            ->end()
        ->getSearchCondition();

        $generator = new QueryConditionGenerator($condition);
        $generator->registerField('id', 'id');
        $generator->registerField('name', 'name');

        self::assertEquals([
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'terms' => [
                                'id' => [
                                    2,
                                    5,
                                ],
                            ],
                        ],
                        [
                            'terms' => [
                                'name' => [
                                    'Doctor',
                                    'Foo',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], $generator->getQuery()->toArray());

        self::assertMapping(['id', 'name'], $generator->getMappings());
    }

    /** @test */
    public function it_generates_a_structure_of_root_level_fields_with_excludes()
    {
        $condition = $this->createCondition()
            ->field('id')
                ->addSimpleValue(10)
                ->addExcludedSimpleValue(5)
            ->end()
        ->getSearchCondition();

        $generator = new QueryConditionGenerator($condition);
        $generator->registerField('id', 'id');
        $generator->registerField('name', 'name');

        self::assertEquals([
            'query' => [
                'bool' => [
                    'must_not' => [
                        [
                            'terms' => [
                                'id' => [5],
                            ],
                        ],
                    ],
                    'must' => [
                        [
                            'terms' => [
                                'id' => [10],
                            ],
                        ],
                    ],
                ],
            ],
        ], $generator->getQuery()->toArray());

        self::assertMapping(['id'], $generator->getMappings());
    }

    /** @test */
    public function it_generates_a_simple_structure_of_nested_fields()
    {
        $condition = $this->createCondition()
            ->field('id')
                ->addSimpleValue(2)
                ->addSimpleValue(5)
            ->end()
            ->group()
                ->field('name')
                    ->addSimpleValue('Doctor')
                    ->addSimpleValue('Foo')
                ->end()
            ->end()
        ->getSearchCondition();

        $generator = new QueryConditionGenerator($condition);
        $generator->registerField('id', 'id');
        $generator->registerField('name', 'name');

        self::assertEquals([
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'terms' => [
                                'id' => [
                                    2,
                                    5,
                                ],
                            ],
                        ],
                        [
                            'bool' => [
                                'must' => [
                                    [
                                        'terms' => [
                                            'name' => [
                                                'Doctor',
                                                'Foo',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], $generator->getQuery()->toArray());

        self::assertMapping(['id', 'name'], $generator->getMappings());
    }

    /** @test */
    public function it_generates_a_structure_with_excludes()
    {
        $condition = $this->createCondition()
            ->field('id')
                ->add(new Range(1, 100))
                ->add(new ExcludedRange(10, 20))
                ->addExcludedSimpleValue(5)
            ->end()
        ->getSearchCondition();

        $generator = new QueryConditionGenerator($condition);
        $generator->registerField('id', 'id');
        $generator->registerField('name', 'name');

        self::assertEquals([
            'query' => [
                'bool' => [
                    'must_not' => [
                        [
                            'terms' => [
                                'id' => [
                                    5,
                                ],
                            ],
                        ],
                        [
                            'range' => [
                                'id' => [
                                    'gte' => 10,
                                    'lte' => 20,
                                ],
                            ],
                        ],
                    ],
                    'must' => [
                        [
                            'range' => [
                                'id' => [
                                    'gte' => 1,
                                    'lte' => 100,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], $generator->getQuery()->toArray());

        self::assertMapping(['id'], $generator->getMappings());
    }

    /** @test */
    public function it_generates_a_structure_with_comparisons()
    {
        $condition = $this->createCondition()
            ->field('id')
                ->add(new Compare(35, '<>'))
                ->add(new Compare(45, '<>'))
                ->add(new Compare(30, '>'))
                ->add(new Compare(50, '<'))
            ->end()
        ->getSearchCondition();

        $generator = new QueryConditionGenerator($condition);
        $generator->registerField('id', 'id');
        $generator->registerField('name', 'name');

        self::assertEquals([
            'query' => [
                'bool' => [
                    'must_not' => [
                        [
                            'term' => [
                                'id' => [
                                    'value' => 35,
                                ],
                            ],
                        ],
                        [
                            'term' => [
                                'id' => [
                                    'value' => 45,
                                ],
                            ],
                        ],
                    ],
                    'must' => [
                        [
                            'id' => [
                                'gt' => 30,
                            ],
                        ],

                        [
                            'id' => [
                                'lt' => 50,
                            ],
                        ],
                    ],
                ],
            ],
        ], $generator->getQuery()->toArray());

        self::assertMapping(['id'], $generator->getMappings());
    }

    /** @test */
    public function it_generates_a_structure_with_PatternMatchers()
    {
        $condition = $this->createCondition()
            ->field('name')
                ->add(new PatternMatch('foo', PatternMatch::PATTERN_STARTS_WITH))
                ->add(new PatternMatch('fo\\\'o', PatternMatch::PATTERN_STARTS_WITH))
                ->add(new PatternMatch('bar', PatternMatch::PATTERN_NOT_ENDS_WITH, true))
                ->add(new PatternMatch('My name', PatternMatch::PATTERN_EQUALS))
                ->add(new PatternMatch('Last', PatternMatch::PATTERN_NOT_EQUALS))
                ->add(new PatternMatch('Spider', PatternMatch::PATTERN_EQUALS, true))
                ->add(new PatternMatch('Piggy', PatternMatch::PATTERN_NOT_EQUALS, true))
            ->end()
        ->getSearchCondition();

        $generator = new QueryConditionGenerator($condition);
        $generator->registerField('id', 'id');
        $generator->registerField('name', 'name');

        self::assertEquals([
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'prefix' => [
                                'name' => [
                                    'value' => 'foo',
                                ],
                            ],
                        ],
                        [
                            'prefix' => [
                                'name' => [
                                    'value' => 'fo\\\'o',
                                ],
                            ],
                        ],
                        [
                            'term' => [
                                'name' => [
                                    'value' => 'My name',
                                ],
                            ],
                        ],
                        [
                            'term' => [
                                'name' => [
                                    'value' => 'Spider',
                                ],
                            ],
                        ],
                    ],
                    'must_not' => [
                        [
                            'wildcard' => [
                                'name' => [
                                    'value' => '?bar',
                                ],
                            ],
                        ],
                        [
                            'term' => [
                                'name' => [
                                    'value' => 'Last',
                                ],
                            ],
                        ],
                        [
                            'term' => [
                                'name' => [
                                    'value' => 'Piggy',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], $generator->getQuery()->toArray());

        self::assertMapping(['name'], $generator->getMappings());
    }

    /** @test */
    public function it_generates_a_structure_with_nested_queries()
    {
        $condition = $this->createCondition()
            ->field('name')
                ->addSimpleValue('Doctor')
                ->addSimpleValue('Foo')
            ->end()
            ->getSearchCondition();

        $generator = new QueryConditionGenerator($condition);
        $generator->registerField('name', 'item[].author[].name');

        self::assertEquals(
            [
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'nested' => [
                                    'path' => 'item',
                                    'query' => [
                                        'nested' => [
                                            'path' => 'author',
                                            'query' => [
                                                'terms' => [
                                                    'author.name' => [
                                                        'Doctor',
                                                        'Foo',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            $generator->getQuery()->toArray()
        );

        self::assertMapping(['name'], $generator->getMappings());
    }

    /** @test */
    public function it_applies_the_primaryCondition_without_a_query()
    {
        $primaryCondition = new SearchPrimaryCondition(
            $this->createCondition()
                ->field('restrict')
                    ->addSimpleValue('Some')
                    ->addSimpleValue('Restriction')
                ->end()
                ->getSearchCondition()
                ->getValuesGroup()
        );

        $condition = $this
            ->createCondition()
            ->getSearchCondition();
        $condition->setPrimaryCondition($primaryCondition);

        $generator = new QueryConditionGenerator($condition);
        $generator->registerField('restrict', 'restrict');

        self::assertEquals(
            [
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'terms' => [
                                    'restrict' => [
                                        'Some',
                                        'Restriction',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            $generator->getQuery()->toArray()
        );

        self::assertMapping(['restrict'], $generator->getMappings());
    }

    /** @test */
    public function it_applies_the_primaryCondition_with_a_query()
    {
        $primaryCondition = new SearchPrimaryCondition(
            $this->createCondition()
                ->field('restrict')
                    ->addSimpleValue('Some')
                    ->addSimpleValue('Restriction')
                ->end()
                ->getSearchCondition()
                ->getValuesGroup()
        );

        $condition = $this
            ->createCondition()
            ->field('name')
                ->addSimpleValue('Doctor')
                ->addSimpleValue('Foo')
            ->end()
            ->getSearchCondition();
        $condition->setPrimaryCondition($primaryCondition);

        $generator = new QueryConditionGenerator($condition);
        $generator->registerField('restrict', 'restrict');
        $generator->registerField('name', 'name');

        self::assertEquals(
            [
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'bool' => [
                                    'must' => [
                                        [
                                            'terms' => [
                                                'restrict' => [
                                                    'Some',
                                                    'Restriction',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                            [
                                'bool' => [
                                    'must' => [
                                        [
                                            'terms' => [
                                                'name' => [
                                                    'Doctor',
                                                    'Foo',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            $generator->getQuery()->toArray()
        );

        self::assertMapping(['restrict', 'name'], $generator->getMappings());
    }

    /** @test */
    public function it_looks_up_by_child_document()
    {
        $condition = $this
            ->createCondition()
                ->field('name')
                    ->addSimpleValue('foo')
                    ->addSimpleValue('bar')
                ->end()
            ->getSearchCondition();

        $generator = new QueryConditionGenerator($condition);
        $generator->registerField('name', 'child>subchild>sub.name');

        self::assertEquals(
            [
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'has_child' => [
                                    'type' => 'child',
                                    'query' => [
                                        'has_child' => [
                                            'type' => 'subchild',
                                            'query' => [
                                                'terms' => [
                                                    'sub.name' => [
                                                        'foo',
                                                        'bar',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            $generator->getQuery()->toArray()
        );

        self::assertMapping(['name'], $generator->getMappings());
    }

    /** @test */
    public function it_adds_contextual_params()
    {
        $condition = $this
            ->createCondition()
                ->field('name')
                    ->addSimpleValue('foo')
                    ->addSimpleValue('bar')
                ->end()
            ->getSearchCondition();

        $generator = new QueryConditionGenerator($condition, new ParameterBag(['locale' => 'en', 'territory' => 'US']));
        $generator->registerField('name', '/articles_{locale}/territory_{territory}#child>name');

        self::assertEquals(
            [
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'has_child' => [
                                    'type' => 'child',
                                    'query' => [
                                        'terms' => [
                                            'name' => [
                                                'foo',
                                                'bar',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            $generator->getQuery()->toArray()
        );

        self::assertSearch(['/articles_en/territory_US'], $generator->getMappings());
        self::assertMapping(['name'], $generator->getMappings());
    }

    /** @test */
    public function it_adds_contextual_conditions_for_has_child_query()
    {
        $condition = $this
            ->createCondition()
                ->field('name')
                    ->addSimpleValue('foo')
                    ->addSimpleValue('bar')
                ->end()
            ->getSearchCondition();

        $generator = new QueryConditionGenerator($condition, new ParameterBag(['locale' => 'en', 'user' => 123]));
        $generator->registerField(
            'name',
            '/articles_{locale}#child>name',
            [
                // these are only applied if the original field is used
                'child>user' => '{user}',
                'another_child>user' => '{user}_{locale}',
                'abc' => ['{user}', 345],
            ]
        );

        self::assertEquals(
            [
                'query' => [
                    'bool' => [
                        'must' => [
                            [
                                'bool' => [
                                    'must' => [
                                        ['has_child' => [
                                            'type' => 'child',
                                            'query' => [
                                                'bool' => [
                                                    'must' => [
                                                        [
                                                            'terms' => [
                                                                'name' => [
                                                                    'foo',
                                                                    'bar',
                                                                ],
                                                            ],
                                                        ],
                                                        [
                                                            'terms' => [
                                                                'user' => [123],
                                                            ],
                                                        ],
                                                    ],
                                                ],
                                            ],
                                        ]],
                                        ['has_child' => [
                                            'type' => 'another_child',
                                            'query' => [
                                                'terms' => [
                                                    'user' => ['123_en'],
                                                ],
                                            ],
                                        ]],
                                        [
                                            'terms' => [
                                                'abc' => [
                                                    '123',
                                                    '345',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            $generator->getQuery()->toArray()
        );

        self::assertSearch(['/articles_en'], $generator->getMappings());
        self::assertMapping(['name'], $generator->getMappings());
    }

    /** @test */
    public function it_adds_sort()
    {
        $condition = $this
            ->createCondition(true)
            ->getSearchCondition();

        $condition->setOrder($this->createSearchOrder(['@date' => 'asc']));

        $generator = new QueryConditionGenerator($condition);
        $generator->registerField('name', '/articles#name');
        $generator->registerField('@date', '/articles#date.keyword');

        self::assertEquals(
            [
                'sort' => [
                    'date.keyword' => ['order' => 'asc'],
                ],
                'query' => [
                    'match_all' => new \stdClass(),
                ],
            ],
            $generator->getQuery()->toArray()
        );

        self::assertSearch(['/articles'], $generator->getMappings());
        self::assertMapping(['@date'], $generator->getMappings());
    }

    /** @test */
    public function it_adds_sort_from_primary_condition()
    {
        $primaryCondition = new SearchPrimaryCondition(
            $this->createCondition(true)
                ->getSearchCondition()
                ->getValuesGroup()
        );
        $primaryCondition->setOrder($this->createSearchOrder(['@id' => 'desc']));

        $condition = $this
            ->createCondition(true)
            ->getSearchCondition();
        $condition->setPrimaryCondition($primaryCondition);

        $condition->setOrder($this->createSearchOrder(['@date' => 'asc']));

        $generator = new QueryConditionGenerator($condition);
        $generator->registerField('name', '/articles#name');
        $generator->registerField('@date', '/articles#date.keyword');
        $generator->registerField('@id', '/articles#_id');

        self::assertEquals(
            [
                'sort' => [
                    'date.keyword' => ['order' => 'asc'],
                    '_id' => ['order' => 'desc'],
                ],
                'query' => [
                    'match_all' => new \stdClass(),
                ],
            ],
            $generator->getQuery()->toArray()
        );

        self::assertSearch(['/articles'], $generator->getMappings());
        self::assertMapping(['@date', '@id'], $generator->getMappings());
    }

    /** @test */
    public function it_adds_sort_for_has_child_query()
    {
        $condition = $this
            ->createCondition(true)
            ->getSearchCondition();
        $condition->setOrder($this->createSearchOrder(['@date' => 'asc']));

        $generator = new QueryConditionGenerator($condition);
        $generator->registerField('@date', '/articles#child>date');

        self::assertEquals([
            'sort' => [
                '_score' => ['order' => 'asc'],
            ],
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'has_child' => [
                                'type' => 'child',
                                'score_mode' => 'max',
                                'query' => [
                                    'function_score' => [
                                        'script_score' => [
                                            'script' => '_score * doc["date"].value',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], $generator->getQuery()->toArray());

        self::assertSearch(['/articles'], $generator->getMappings());
        self::assertMapping(['@date'], $generator->getMappings());
    }

    /** @test */
    public function it_merges_has_child_queries_from_conditional_order_queries()
    {
        $condition = $this
            ->createCondition(true)
            ->getSearchCondition();
        $condition->setOrder($this->createSearchOrder(['@date' => 'asc']));

        $generator = new QueryConditionGenerator($condition, new ParameterBag(['user' => 123]));
        $generator->registerField('@date', '/articles#child>some.date', [
            'child>user' => '{user}',
        ]);

        self::assertEquals([
            'sort' => [
                '_score' => ['order' => 'asc'],
            ],
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'has_child' => [
                                'type' => 'child',
                                'score_mode' => 'max',
                                'query' => [
                                    'bool' => [
                                        'must' => [
                                            [
                                                'terms' => [
                                                    'user' => [
                                                        123,
                                                    ],
                                                ],
                                            ],
                                            [
                                                'function_score' => [
                                                    'script_score' => [
                                                        'script' => '_score * doc["some.date"].value',
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], $generator->getQuery()->toArray());

        self::assertSearch(['/articles'], $generator->getMappings());
        self::assertMapping(['@date'], $generator->getMappings());
    }

    protected function getFieldSet(bool $build = true, bool $order = false)
    {
        $fieldSet = parent::getFieldSet(false);

        if ($order) {
            $fieldSet->add('@date', OrderFieldType::class);
            $fieldSet->add('@id', OrderFieldType::class, ['default' => 'DESC']);
        }

        return $build ? $fieldSet->getFieldSet() : $fieldSet;
    }

    /**
     * @param bool $order
     *
     * @return SearchConditionBuilder
     */
    private function createCondition(bool $order = false): SearchConditionBuilder
    {
        /** @var FieldSet $fieldSet */
        $fieldSet = $this->getFieldSet(true, $order);

        return SearchConditionBuilder::create($fieldSet);
    }

    private function createSearchOrder(array $fields): SearchOrder
    {
        $valuesGroup = new ValuesGroup();
        foreach ($fields as $field => $direction) {
            $valuesBag = new ValuesBag();
            $valuesBag->addSimpleValue($direction);

            $valuesGroup->addField($field, $valuesBag);
        }

        return new SearchOrder($valuesGroup);
    }

    /**
     * @param string[]       $expected
     * @param FieldMapping[] $mappings
     */
    private static function assertSearch(array $expected, array $mappings)
    {
        $actual = [];
        foreach ($mappings as $mapping) {
            $search = null;
            if ($mapping->indexName) {
                $search = '/'.$mapping->indexName;

                if ($mapping->typeName) {
                    $search .= '/'.$mapping->typeName;
                }
            }

            $actual[] = $search;
        }

        sort($expected);
        sort($actual);

        self::assertEquals($expected, array_unique($actual));
    }

    /**
     * @param string[]       $expected
     * @param FieldMapping[] $mappings
     */
    private static function assertMapping(array $expected, array $mappings)
    {
        $actual = [];
        foreach ($mappings as $mapping) {
            $actual[] = $mapping->fieldName;
        }

        sort($expected);
        sort($actual);

        self::assertEquals($expected, $actual);
    }
}
