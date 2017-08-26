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
        $condition = SearchConditionBuilder::create($this->getFieldSet())->getSearchCondition();
        $g = new QueryConditionGenerator($condition);

        self::assertNull($g->getQuery());
    }

    /** @test */
    public function it_generates_a_structure_of_root_level_fields()
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

        self::assertEquals([
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
        ], $g->getQuery());
    }

    /** @test */
    public function it_generates_a_structure_of_root_level_fields_with_excludes()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('id')
                ->addSimpleValue(10)
                ->addExcludedSimpleValue(5)
            ->end()
        ->getSearchCondition();

        $g = new QueryConditionGenerator($condition);

        self::assertEquals([
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
        ], $g->getQuery());
    }

    /** @test */
    public function it_generates_a_simple_structure_of_nested_fields()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
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

        $g = new QueryConditionGenerator($condition);

        self::assertEquals([
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
        ], $g->getQuery());
    }

    /** @test */
    public function it_generates_a_structure_with_excludes()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('id')
                ->add(new Range(1, 100))
                ->add(new ExcludedRange(10, 20))
                ->addExcludedSimpleValue(5)
            ->end()
        ->getSearchCondition();

        $g = new QueryConditionGenerator($condition);

        self::assertEquals([
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
                        'id' => [
                            'lte' => 10,
                            'gte' => 20,
                        ],
                    ],
                ],
                'must' => [
                    [
                        'id' => [
                            'lte' => 1,
                            'gte' => 100,
                        ],
                    ],
                ],
            ],
        ], $g->getQuery());
    }

    /** @test */
    public function it_generates_a_structure_with_comparisons()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('id')
                ->add(new Compare(35, '<>'))
                ->add(new Compare(45, '<>'))
                ->add(new Compare(30, '>'))
                ->add(new Compare(50, '<'))
            ->end()
        ->getSearchCondition();

        $g = new QueryConditionGenerator($condition);

        self::assertEquals([
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
        ], $g->getQuery());
    }

    /** @test */
    public function it_generates_a_structure_with_PatternMatchers()
    {
        $condition = SearchConditionBuilder::create($this->getFieldSet())
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

        $g = new QueryConditionGenerator($condition);

        self::assertEquals([
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
        ], $g->getQuery());
    }
}
