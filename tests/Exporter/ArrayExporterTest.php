<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests\Exporter;

use Rollerworks\Component\Search\Exporter\ArrayExporter;
use Rollerworks\Component\Search\ExporterInterface;
use Rollerworks\Component\Search\Input\ArrayInput;
use Rollerworks\Component\Search\InputProcessorInterface;

final class ArrayExporterTest extends SearchConditionExporterTestCase
{
    public function provideSingleValuePairTest()
    {
        return [
            'fields' => [
                'name' => [
                    'single-values' => [
                        'value ',
                        '-value2',
                        'value2-',
                        '10.00',
                        '10,00',
                        'hÌ',
                        '٤٤٤٦٥٤٦٠٠',
                        'doctor"who""',
                    ],
                    'excluded-values' => ['value3'],
                ],
            ],
        ];
    }

    public function provideFieldAliasTest()
    {
        return [
            'fields' => [
                'firstname' => [
                    'single-values' => [
                        'value',
                        'value2',
                    ],
                ],
            ],
        ];
    }

    public function provideMultipleValuesTest()
    {
        return [
            'fields' => [
                'name' => [
                    'single-values' => ['value', 'value2'],
                ],
                'date' => [
                    'single-values' => ['12-16-2014'],
                ],
            ],
        ];
    }

    public function provideRangeValuesTest()
    {
        return [
            'fields' => [
                'id' => [
                    'ranges' => [
                        ['lower' => 1, 'upper' => 10],
                        ['lower' => 15, 'upper' => 30],
                        ['lower' => 100, 'upper' => 200, 'inclusive-lower' => false],
                        ['lower' => 310, 'upper' => 400, 'inclusive-upper' => false],
                    ],
                    'excluded-ranges' => [
                        ['lower' => 50, 'upper' => 70],
                    ],
                ],
                'date' => [
                    'ranges' => [
                        ['lower' => '12-16-2014', 'upper' => '12-20-2014'],
                    ],
                ],
            ],
        ];
    }

    public function provideComparisonValuesTest()
    {
        return [
            'fields' => [
                'id' => [
                    'comparisons' => [
                        ['value' => 1, 'operator' => '>'],
                        ['value' => 2, 'operator' => '<'],
                        ['value' => 5, 'operator' => '<='],
                        ['value' => 8, 'operator' => '>='],
                    ],
                ],
                'date' => [
                    'comparisons' => [
                        ['value' => '12-16-2014', 'operator' => '>='],
                    ],
                ],
            ],
        ];
    }

    public function provideMatcherValuesTest()
    {
        return [
            'fields' => [
                'name' => [
                    'pattern-matchers' => [
                        ['type' => 'CONTAINS', 'value' => 'value', 'case-insensitive' => false],
                        ['type' => 'STARTS_WITH', 'value' => 'value2', 'case-insensitive' => true],
                        ['type' => 'ENDS_WITH', 'value' => 'value3', 'case-insensitive' => false],
                        ['type' => 'REGEX', 'value' => '^foo|bar?', 'case-insensitive' => false],
                        ['type' => 'NOT_CONTAINS', 'value' => 'value4', 'case-insensitive' => false],
                        ['type' => 'NOT_CONTAINS', 'value' => 'value5', 'case-insensitive' => true],
                    ],
                ],
            ],
        ];
    }

    public function provideGroupTest()
    {
        return [
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
        ];
    }

    public function provideMultipleSubGroupTest()
    {
        return [
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
        ];
    }

    public function provideNestedGroupTest()
    {
        return [
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
        ];
    }

    public function provideEmptyValuesTest()
    {
        return [];
    }

    public function provideEmptyGroupTest()
    {
        return ['groups' => [[]]];
    }

    /**
     * @return ExporterInterface
     */
    protected function getExporter()
    {
        return new ArrayExporter($this->fieldLabelResolver->reveal());
    }

    /**
     * @return InputProcessorInterface
     */
    protected function getInputProcessor()
    {
        return new ArrayInput($this->fieldAliasResolver->reveal());
    }
}
