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

use Rollerworks\Component\Search\Input\JsonInput;
use Rollerworks\Component\Search\Input\ProcessorConfig;
use Rollerworks\Component\Search\Value\ValuesGroup;
use Rollerworks\Component\Search\ValuesError;

final class JsonInputTest extends InputProcessorTestCase
{
    protected function getProcessor()
    {
        return new JsonInput();
    }

    /**
     * @test
     */
    public function it_errors_on_invalid_json()
    {
        $processor = $this->getProcessor();
        $config = new ProcessorConfig($this->getFieldSet());

        $this->setExpectedException(
            'Rollerworks\Component\Search\Exception\InputProcessorException',
            "Input does not contain valid JSON: \nParse error on line 1:\n{]\n^\nExpected one of: 'STRING', '}'"
        );

        $processor->process($config, '{]');
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
                                'single-values' => ['value', 'value2', '٤٤٤٦٥٤٦٠٠', '30', '30L'],
                                'excluded-values' => ['value3'],
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
                                'single-values' => ['value', 'value2'],
                            ],
                            'date' => [
                                'single-values' => ['12-16-2014'],
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
                                    ['lower' => '12-16-2014', 'upper' => '12-20-2014'],
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
                                    ['value' => '12-16-2014', 'operator' => '>='],
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
                                    ['value' => '^foo|bar?', 'type' => 'REGEX'],
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
                                'single-values' => ['value', 'value2'],
                            ],
                        ],
                        'groups' => [
                            [
                                'fields' => [
                                    'name' => [
                                        'single-values' => ['value3', 'value4'],
                                    ],
                                ],
                            ],
                            [
                                'logical-case' => 'OR',
                                'fields' => [
                                    'name' => [
                                        'single-values' => ['value8', 'value10'],
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
                                'single-values' => ['value', 'value2'],
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
                                'single-values' => ['value', 'value2'],
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
                                'single-values' => ['value', 'value2'],
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
                                        'single-values' => ['value', 'value2'],
                                    ],
                                ],
                            ],
                            [
                                'fields' => [
                                    'name' => [
                                        'single-values' => ['value3', 'value4'],
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
                                                'single-values' => ['value', 'value2'],
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
                                'single-values' => ['value', 'value2', 'value3', 'value4'],
                            ],
                        ],
                    ]
                ),
                'name',
                3,
                0,
                0,
            ],
            [
                json_encode(
                    [
                        'groups' => [
                            [
                                'fields' => [
                                    'name' => [
                                        'single-values' => ['value', 'value2', 'value3', 'value4'],
                                    ],
                                ],
                            ],
                        ],
                    ]
                ),
                'name',
                3,
                0,
                1,
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
                                                'single-values' => ['value', 'value2', 'value3', 'value4'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ]
                ),
                'name',
                3,
                0,
                2,
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
                                                'single-values' => ['value', 'value2'],
                                            ],
                                        ],
                                    ],
                                    [
                                        'fields' => [
                                            'name' => [
                                                'single-values' => ['value', 'value2', 'value3', 'value4'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ]
                ),
                'name',
                3,
                1,
                2,
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
                                        'single-values' => ['value', 'value2'],
                                    ],
                                ],
                            ],
                            [
                                'fields' => [
                                    'name' => [
                                        'single-values' => ['value', 'value2'],
                                    ],
                                ],
                            ],
                            [
                                'fields' => [
                                    'name' => [
                                        'single-values' => ['value', 'value2'],
                                    ],
                                ],
                            ],
                            [
                                'fields' => [
                                    'name' => [
                                        'single-values' => ['value', 'value2'],
                                    ],
                                ],
                            ],
                        ],
                    ]
                ),
                3,
                4,
                0,
                0,
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
                                                        'single-values' => ['value', 'value2'],
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
                                                        'single-values' => ['value', 'value2'],
                                                    ],
                                                ],
                                            ],
                                            [
                                                'fields' => [
                                                    'name' => [
                                                        'single-values' => ['value', 'value2'],
                                                    ],
                                                ],
                                            ],
                                            [
                                                'fields' => [
                                                    'name' => [
                                                        'single-values' => ['value', 'value2'],
                                                    ],
                                                ],
                                            ],
                                            [
                                                'fields' => [
                                                    'name' => [
                                                        'single-values' => ['value', 'value2'],
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
            3,
            4,
            1,
            2,
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
                                                'single-values' => ['value', 'value2'],
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

    public function provideUnknownFieldTests()
    {
        return [
            [
                json_encode(
                    [
                        'fields' => [
                            'field2' => [
                                'single-values' => ['value', 'value2'],
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
                'range',
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
                'range',
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
                'comparison',
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
                'pattern-match',
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
                true,
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
                                'single-values' => ['foo', '30', 'bar'],
                                'comparisons' => [['operator' => '>', 'value' => 'life']],
                            ],
                        ],
                    ]
                ),
                'id',
                [
                    new ValuesError('singleValues[0]', 'This value is not valid.'),
                    new ValuesError('singleValues[2]', 'This value is not valid.'),
                    new ValuesError('comparisons[0].value', 'This value is not valid.'),
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
                                                'single-values' => ['value', 'value2'],
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
}
