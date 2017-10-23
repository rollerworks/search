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

use Rollerworks\Component\Search\Elasticsearch\QueryConditionGenerator;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\SearchConditionBuilder;
use Rollerworks\Component\Search\Test\SearchIntegrationTestCase;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\ExcludedRange;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;

final class QueryConditionGeneratorTest extends SearchIntegrationTestCase
{
    /** @test */
    public function it_generates_nothing_for_empty_condition()
    {
        $condition = $this->createCondition()->getSearchCondition();
        $generator = new QueryConditionGenerator($condition);
        $this->addMappings($generator);

        self::assertNull($generator->getQuery());
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
        $this->addMappings($generator);

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
        ], $generator->getQuery());
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
        $this->addMappings($generator);

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
        ], $generator->getQuery());
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
        $this->addMappings($generator);

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
        ], $generator->getQuery());
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
        $this->addMappings($generator);

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
        ], $generator->getQuery());
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
        $this->addMappings($generator);

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
        ], $generator->getQuery());
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
        $this->addMappings($generator);

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
        ], $generator->getQuery());
    }

    private function addMappings(QueryConditionGenerator $generator)
    {
        $generator->registerField('id', 'id');
        $generator->registerField('name', 'name');
    }

    /**
     * @return SearchConditionBuilder
     */
    private function createCondition(): SearchConditionBuilder
    {
        /** @var FieldSet $fieldSet */
        $fieldSet = $this->getFieldSet();

        return SearchConditionBuilder::create($fieldSet);
    }
}
