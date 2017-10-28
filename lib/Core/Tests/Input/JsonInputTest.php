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

namespace Rollerworks\Component\Search\Tests\Input;

use Rollerworks\Component\Search\ConditionErrorMessage;
use Rollerworks\Component\Search\Input\JsonInput;
use Rollerworks\Component\Search\Input\ProcessorConfig;
use Rollerworks\Component\Search\InputProcessor;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\ValuesGroup;

/**
 * @internal
 */
final class JsonInputTest extends InputProcessorTestCase
{
    protected function getProcessor(): InputProcessor
    {
        return new JsonInput();
    }

    /**
     * @test
     */
    public function it_errors_on_invalid_json()
    {
        $config = new ProcessorConfig($this->getFieldSet());
        $error = ConditionErrorMessage::rawMessage(
            '{]',
            "Input does not contain valid JSON: \nState mismatch (invalid or malformed JSON)"
        );

        $this->assertConditionContainsErrorsWithoutCause('{]', $config, [$error]);
    }

    public function provideEmptyInputTests()
    {
        return [
            [''],
            ['{}'],
        ];
    }

    public function provideSingleValuePairTests()
    {
        return [
            [
                json_encode(
                    [
                        'fields' => [
                            'name' => [
                                'simple-values' => ['value', 'value2', '٤٤٤٦٥٤٦٠٠', '30', '30L'],
                                'excluded-simple-values' => ['value3'],
                            ],
                        ],
                    ]
                ),
            ],
        ];
    }

    public function provideMultipleValues()
    {
        return [
            [
                json_encode(
                    [
                        'fields' => [
                            'name' => [
                                'simple-values' => ['value', 'value2'],
                            ],
                            'date' => [
                                'simple-values' => ['2014-12-16T00:00:00Z'],
                            ],
                        ],
                    ]
                ),
            ],
        ];
    }

    public function provideRangeValues()
    {
        return [
            [
                json_encode(
                    [
                        'fields' => [
                            'id' => [
                                'ranges' => [
                                    ['lower' => 1, 'upper' => 10],
                                    ['lower' => 15, 'upper' => 30],
                                    ['lower' => 100, 'upper' => 200, 'inclusive-lower' => false],
                                    ['lower' => 310, 'upper' => 400, 'inclusive-upper' => false],
                                ],
                                'excluded-ranges' => [
                                    ['lower' => 50, 'upper' => 70, 'inclusive-lower' => true],
                                ],
                            ],
                            'date' => [
                                'ranges' => [
                                    ['lower' => '2014-12-16T00:00:00Z', 'upper' => '2014-12-20T00:00:00Z'],
                                ],
                            ],
                        ],
                    ]
                ),
            ],
        ];
    }

    public function provideComparisonValues()
    {
        return [
            [
                json_encode(
                    [
                        'fields' => [
                            'id' => [
                                'comparisons' => [
                                    ['value' => 1, 'operator' => '>'],
                                    ['value' => 2, 'operator' => '<'],
                                    ['value' => 5, 'operator' => '<='],
                                    ['value' => 8, 'operator' => '>='],
                                    ['value' => 20, 'operator' => '<>'],
                                ],
                            ],
                            'date' => [
                                'comparisons' => [
                                    ['value' => '2014-12-16T00:00:00Z', 'operator' => '>='],
                                ],
                            ],
                        ],
                    ]
                ),
            ],
        ];
    }

    public function provideMatcherValues()
    {
        return [
            [
                json_encode(
                    [
                        'fields' => [
                            'name' => [
                                'pattern-matchers' => [
                                    ['value' => 'value', 'type' => 'CONTAINS'],
                                    ['value' => 'value2', 'type' => 'STARTS_WITH', 'case-insensitive' => true],
                                    ['value' => 'value3', 'type' => 'ENDS_WITH'],
                                    ['value' => 'value4', 'type' => 'NOT_CONTAINS'],
                                    ['value' => 'value5', 'type' => 'NOT_CONTAINS', 'case-insensitive' => true],
                                    ['value' => 'value9', 'type' => 'EQUALS'],
                                    ['value' => 'value10', 'type' => 'NOT_EQUALS'],
                                    ['value' => 'value11', 'type' => 'EQUALS', 'case-insensitive' => true],
                                    ['value' => 'value12', 'type' => 'NOT_EQUALS', 'case-insensitive' => true],
                                ],
                            ],
                        ],
                    ]
                ),
            ],
        ];
    }

    public function provideGroupTests()
    {
        return [
            [
                json_encode(
                    [
                        'fields' => [
                            'name' => [
                                'simple-values' => ['value', 'value2'],
                            ],
                        ],
                        'groups' => [
                            [
                                'fields' => [
                                    'name' => [
                                        'simple-values' => ['value3', 'value4'],
                                    ],
                                ],
                            ],
                            [
                                'logical-case' => 'OR',
                                'fields' => [
                                    'name' => [
                                        'simple-values' => ['value8', 'value10'],
                                    ],
                                ],
                            ],
                        ],
                    ]
                ),
            ],
        ];
    }

    public function provideRootLogicalTests()
    {
        return [
            [
                json_encode(
                    [
                        'fields' => [
                            'name' => [
                                'simple-values' => ['value', 'value2'],
                            ],
                        ],
                    ]
                ),
            ],
            [
                json_encode(
                    [
                        'logical-case' => 'AND',
                        'fields' => [
                            'name' => [
                                'simple-values' => ['value', 'value2'],
                            ],
                        ],
                    ]
                ),
            ],
            [
                json_encode(
                    [
                        'logical-case' => 'OR',
                        'fields' => [
                            'name' => [
                                'simple-values' => ['value', 'value2'],
                            ],
                        ],
                    ]
                ),
                ValuesGroup::GROUP_LOGICAL_OR,
            ],
        ];
    }

    public function provideMultipleSubGroupTests()
    {
        return [
            [
                json_encode(
                    [
                        'groups' => [
                            [
                                'fields' => [
                                    'name' => [
                                        'simple-values' => ['value', 'value2'],
                                    ],
                                ],
                            ],
                            [
                                'fields' => [
                                    'name' => [
                                        'simple-values' => ['value3', 'value4'],
                                    ],
                                ],
                            ],
                        ],
                    ]
                ),
            ],
        ];
    }

    public function provideNestedGroupTests()
    {
        return [
            [
                json_encode(
                    [
                        'groups' => [
                            [
                                'groups' => [
                                    [
                                        'fields' => [
                                            'name' => [
                                                'simple-values' => ['value', 'value2'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ]
                ),
            ],
        ];
    }

    public function provideValueOverflowTests()
    {
        return [
            [
                json_encode(
                    [
                        'fields' => [
                            'name' => [
                                'simple-values' => ['value', 'value2', 'value3', 'value4'],
                            ],
                        ],
                    ]
                ),
                'name',
                '[fields][name][simple-values][3]',
            ],
            [
                json_encode(
                    [
                        'groups' => [
                            [
                                'fields' => [
                                    'name' => [
                                        'simple-values' => ['value', 'value2', 'value3', 'value4'],
                                    ],
                                ],
                            ],
                        ],
                    ]
                ),
                'name',
                '[groups][0][fields][name][simple-values][3]',
            ],
            [
                json_encode(
                    [
                        'groups' => [
                            [
                                'groups' => [
                                    [
                                        'fields' => [
                                            'name' => [
                                                'simple-values' => ['value', 'value2', 'value3', 'value4'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ]
                ),
                'name',
                '[groups][0][groups][0][fields][name][simple-values][3]',
            ],
            [
                json_encode(
                    [
                        'groups' => [
                            [
                                'groups' => [
                                    [
                                        'fields' => [
                                            'name' => [
                                                'simple-values' => ['value', 'value2'],
                                            ],
                                        ],
                                    ],
                                    [
                                        'fields' => [
                                            'name' => [
                                                'simple-values' => ['value', 'value2', 'value3', 'value4'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ]
                ),
                'name',
                '[groups][0][groups][1][fields][name][simple-values][3]',
            ],
        ];
    }

    public function provideGroupsOverflowTests()
    {
        return [
            [
                json_encode(
                    [
                        'groups' => [
                            [
                                'fields' => [
                                    'name' => [
                                        'simple-values' => ['value', 'value2'],
                                    ],
                                ],
                            ],
                            [
                                'fields' => [
                                    'name' => [
                                        'simple-values' => ['value', 'value2'],
                                    ],
                                ],
                            ],
                            [
                                'fields' => [
                                    'name' => [
                                        'simple-values' => ['value', 'value2'],
                                    ],
                                ],
                            ],
                            [
                                'fields' => [
                                    'name' => [
                                        'simple-values' => ['value', 'value2'],
                                    ],
                                ],
                            ],
                        ],
                    ]
                ),
                '',
            ],
            [
                json_encode(
                    [
                        'groups' => [
                            [
                                'groups' => [
                                    [
                                        'groups' => [
                                            [
                                                'fields' => [
                                                    'name' => [
                                                        'simple-values' => ['value', 'value2'],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                    [
                                        'groups' => [
                                            [
                                                'fields' => [
                                                    'name' => [
                                                        'simple-values' => ['value', 'value2'],
                                                    ],
                                                ],
                                            ],
                                            [
                                                'fields' => [
                                                    'name' => [
                                                        'simple-values' => ['value', 'value2'],
                                                    ],
                                                ],
                                            ],
                                            [
                                                'fields' => [
                                                    'name' => [
                                                        'simple-values' => ['value', 'value2'],
                                                    ],
                                                ],
                                            ],
                                            [
                                                'fields' => [
                                                    'name' => [
                                                        'simple-values' => ['value', 'value2'],
                                                    ],
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ]
                ),
                '[groups][0][groups][1]',
            ],
        ];
    }

    public function provideNestingLevelExceededTests()
    {
        return [
            [
                json_encode(
                    [
                        'groups' => [
                            [
                                'groups' => [
                                    [
                                        'fields' => [
                                            'name' => [
                                                'simple-values' => ['value', 'value2'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ]
                ),
                '[groups][0][groups][0]',
            ],
        ];
    }

    public function provideUnknownFieldTests()
    {
        return [
            [
                json_encode(
                    [
                        'fields' => [
                            'field2' => [
                                'simple-values' => ['value', 'value2'],
                            ],
                        ],
                    ]
                ),
            ],
        ];
    }

    public function provideUnsupportedValueTypeExceptionTests()
    {
        return [
            [
                json_encode(
                    [
                        'fields' => [
                            'no-range-field' => [
                                'ranges' => [['lower' => 10, 'upper' => 20]],
                            ],
                        ],
                    ]
                ),
                'no-range-field',
               Range::class,
            ],
            [
                json_encode(
                    [
                        'fields' => [
                            'no-range-field' => [
                                'excluded-ranges' => [['lower' => 10, 'upper' => 20]],
                            ],
                        ],
                    ]
                ),
                'no-range-field',
                Range::class,
            ],
            [
                json_encode(
                    [
                        'fields' => [
                            'no-compares-field' => [
                                'comparisons' => [['value' => 10, 'operator' => '>']],
                            ],
                        ],
                    ]
                ),
                'no-compares-field',
                Compare::class,
            ],
            [
                json_encode(
                    [
                        'fields' => [
                            'no-matchers-field' => [
                                'pattern-matchers' => [['value' => 'foo', 'type' => 'CONTAINS']],
                            ],
                        ],
                    ]
                ),
                'no-matchers-field',
                PatternMatch::class,
            ],
        ];
    }

    /**
     * @return array[]
     */
    public function provideInvalidRangeTests()
    {
        return [
            [
                json_encode(
                    [
                        'fields' => [
                            'id' => [
                                'ranges' => [
                                    ['lower' => 30, 'upper' => 10],
                                    ['lower' => 50, 'upper' => 60],
                                    ['lower' => 40, 'upper' => 20],
                                ],
                            ],
                        ],
                    ]
                ),
                ['[fields][id][ranges][0]', '[fields][id][ranges][2]'],
            ],
            [
                json_encode(
                    [
                        'fields' => [
                            'id' => [
                                'excluded-ranges' => [
                                    ['lower' => 30, 'upper' => 10],
                                    ['lower' => 50, 'upper' => 60],
                                    ['lower' => 40, 'upper' => 20],
                                ],
                            ],
                        ],
                    ]
                ),
                ['[fields][id][excluded-ranges][0]', '[fields][id][excluded-ranges][2]'],
            ],
        ];
    }

    public function provideInvalidValueTests()
    {
        return [
            [
                json_encode(
                    [
                        'fields' => [
                            'id' => [
                                'simple-values' => ['foo', '30', 'bar'],
                                'comparisons' => [['operator' => '>', 'value' => 'life']],
                            ],
                        ],
                    ]
                ),
                [
                    new ConditionErrorMessage('[fields][id][simple-values][0]', 'This value is not valid.'),
                    new ConditionErrorMessage('[fields][id][simple-values][2]', 'This value is not valid.'),
                    new ConditionErrorMessage('[fields][id][comparisons][0][value]', 'This value is not valid.'),
                ],
            ],
        ];
    }

    public function provideNestedErrorsTests()
    {
        return [
            [
                json_encode(
                    [
                        'groups' => [
                            [
                                'groups' => [
                                    [
                                        'fields' => [
                                            'date' => [
                                                'simple-values' => ['value'],
                                            ],
                                        ],
                                    ],
                                    [
                                        'fields' => [
                                            'date' => [
                                                'simple-values' => ['value', 'value2'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ]
                ),
                [
                    new ConditionErrorMessage('[groups][0][groups][0][fields][date][simple-values][0]', 'This value is not valid.'),
                    new ConditionErrorMessage('[groups][0][groups][1][fields][date][simple-values][0]', 'This value is not valid.'),
                    new ConditionErrorMessage('[groups][0][groups][1][fields][date][simple-values][1]', 'This value is not valid.'),
                ],
            ],
        ];
    }
}
