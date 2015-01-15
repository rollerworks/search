<?php

/*
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests\Input;

use Rollerworks\Component\Search\Input\JsonInput;
use Rollerworks\Component\Search\Input\ProcessorConfig;
use Rollerworks\Component\Search\ValuesError;
use Rollerworks\Component\Search\ValuesGroup;

final class JsonInputTest extends InputProcessorTestCase
{
    protected function getProcessor()
    {
        return new JsonInput($this->fieldAliasResolver->reveal());
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
        return array(
            array(''),
            array('{}'),
        );
    }

    public function provideSingleValuePairTests()
    {
        return array(
            array(
                json_encode(
                    array(
                        'fields' => array(
                            'name' => array(
                                'single-values' => array('value', 'value2', '٤٤٤٦٥٤٦٠٠', '30', '30L'),
                                'excluded-values' => array('value3'),
                            ),
                        ),
                    )
                ),
            ),
        );
    }

    public function provideMultipleValues()
    {
        return array(
            array(
                json_encode(
                    array(
                        'fields' => array(
                            'name' => array(
                                'single-values' => array('value', 'value2'),
                            ),
                            'date' => array(
                                'single-values' => array('12-16-2014'),
                            ),
                        ),
                    )
                ),
            ),
        );
    }

    public function provideRangeValues()
    {
        return array(
            array(
                json_encode(
                    array(
                        'fields' => array(
                            'id' => array(
                                'ranges' => array(
                                    array('lower' => 1, 'upper' => 10),
                                    array('lower' => 15, 'upper' => 30),
                                    array('lower' => 100, 'upper' => 200, 'inclusive-lower' => false),
                                    array('lower' => 310, 'upper' => 400, 'inclusive-upper' => false),
                                ),
                                'excluded-ranges' => array(
                                    array('lower' => 50, 'upper' => 70, 'inclusive-lower' => true),
                                ),
                            ),
                            'date' => array(
                                'ranges' => array(
                                    array('lower' => '12-16-2014', 'upper' => '12-20-2014'),
                                ),
                            ),
                        ),
                    )
                ),
            ),
        );
    }

    public function provideComparisonValues()
    {
        return array(
            array(
                json_encode(
                    array(
                        'fields' => array(
                            'id' => array(
                                'comparisons' => array(
                                    array('value' => 1, 'operator' => '>'),
                                    array('value' => 2, 'operator' => '<'),
                                    array('value' => 5, 'operator' => '<='),
                                    array('value' => 8, 'operator' => '>='),
                                ),
                            ),
                            'date' => array(
                                'comparisons' => array(
                                    array('value' => '12-16-2014', 'operator' => '>='),
                                ),
                            ),
                        ),
                    )
                ),
            ),
        );
    }

    public function provideMatcherValues()
    {
        return array(
            array(
                json_encode(
                    array(
                        'fields' => array(
                            'name' => array(
                                'pattern-matchers' => array(
                                    array('value' => 'value', 'type' => 'CONTAINS'),
                                    array('value' => 'value2', 'type' => 'STARTS_WITH', 'case-insensitive' => true),
                                    array('value' => 'value3', 'type' => 'ENDS_WITH'),
                                    array('value' => '^foo|bar?', 'type' => 'REGEX'),
                                    array('value' => 'value4', 'type' => 'NOT_CONTAINS'),
                                    array('value' => 'value5', 'type' => 'NOT_CONTAINS', 'case-insensitive' => true),
                                ),
                            ),
                        ),
                    )
                ),
            ),
        );
    }

    public function provideGroupTests()
    {
        return array(
            array(
                json_encode(
                    array(
                        'fields' => array(
                            'name' => array(
                                'single-values' => array('value', 'value2'),
                            ),
                        ),
                        'groups' => array(
                            array(
                                'fields' => array(
                                    'name' => array(
                                        'single-values' => array('value3', 'value4'),
                                    ),
                                ),
                            ),
                            array(
                                'logical-case' => 'OR',
                                'fields' => array(
                                    'name' => array(
                                        'single-values' => array('value8', 'value10'),
                                    ),
                                ),
                            ),
                        ),
                    )
                ),
            ),
        );
    }

    public function provideRootLogicalTests()
    {
        return array(
            array(
                json_encode(
                    array(
                        'fields' => array(
                            'name' => array(
                                'single-values' => array('value', 'value2'),
                            ),
                        ),
                    )
                ),
            ),
            array(
                json_encode(
                    array(
                        'logical-case' => 'AND',
                        'fields' => array(
                            'name' => array(
                                'single-values' => array('value', 'value2'),
                            ),
                        ),
                    )
                ),
            ),
            array(
                json_encode(
                    array(
                        'logical-case' => 'OR',
                        'fields' => array(
                            'name' => array(
                                'single-values' => array('value', 'value2'),
                            ),
                        ),
                    )
                ),
                ValuesGroup::GROUP_LOGICAL_OR
            ),
        );
    }

    public function provideMultipleSubGroupTests()
    {
        return array(
            array(
                json_encode(
                    array(
                        'groups' => array(
                            array(
                                'fields' => array(
                                    'name' => array(
                                        'single-values' => array('value', 'value2'),
                                    ),
                                ),
                            ),
                            array(
                                'fields' => array(
                                    'name' => array(
                                        'single-values' => array('value3', 'value4'),
                                    ),
                                ),
                            ),
                        ),
                    )
                ),
            ),
        );
    }

    public function provideNestedGroupTests()
    {
        return array(
            array(
                json_encode(
                    array(
                        'groups' => array(
                            array(
                                'groups' => array(
                                    array(
                                        'fields' => array(
                                            'name' => array(
                                                'single-values' => array('value', 'value2'),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    )
                ),
            ),
        );
    }

    public function provideValueOverflowTests()
    {
        return array(
            array(
                json_encode(
                    array(
                        'fields' => array(
                            'name' => array(
                                'single-values' => array('value', 'value2', 'value3', 'value4'),
                            ),
                        ),
                    )
                ),
                'name',
                3,
                4,
                0,
                0,
            ),
            array(
                json_encode(
                    array(
                        'groups' => array(
                            array(
                                'fields' => array(
                                    'name' => array(
                                        'single-values' => array('value', 'value2', 'value3', 'value4'),
                                    ),
                                ),
                            ),
                        ),
                    )
                ),
                'name',
                3,
                4,
                0,
                1,
            ),
            array(
                json_encode(
                    array(
                        'groups' => array(
                            array(
                                'groups' => array(
                                    array(
                                        'fields' => array(
                                            'name' => array(
                                                'single-values' => array('value', 'value2', 'value3', 'value4'),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    )
                ),
                'name',
                3,
                4,
                0,
                2,
            ),
            array(
                json_encode(
                    array(
                        'groups' => array(
                            array(
                                'groups' => array(
                                    array(
                                        'fields' => array(
                                            'name' => array(
                                                'single-values' => array('value', 'value2'),
                                            ),
                                        ),
                                    ),
                                    array(
                                        'fields' => array(
                                            'name' => array(
                                                'single-values' => array('value', 'value2', 'value3', 'value4'),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    )
                ),
                'name',
                3,
                4,
                1,
                2,
            ),
            array(
                json_encode(
                    array(
                        'fields' => array(
                            'id' => array(
                                'single-values' => array('1', '2'),
                            ),
                            'user-id' => array(
                                'single-values' => array('3', '4', '5'),
                            ),
                        ),
                    )
                ),
                'id',
                3,
                5,
                0,
                0,
            ),
        );
    }

    public function provideGroupsOverflowTests()
    {
        return array(
            array(
                json_encode(
                    array(
                        'groups' => array(
                            array(
                                'fields' => array(
                                    'name' => array(
                                        'single-values' => array('value', 'value2'),
                                    ),
                                ),
                            ),
                            array(
                                'fields' => array(
                                    'name' => array(
                                        'single-values' => array('value', 'value2'),
                                    ),
                                ),
                            ),
                            array(
                                'fields' => array(
                                    'name' => array(
                                        'single-values' => array('value', 'value2'),
                                    ),
                                ),
                            ),
                            array(
                                'fields' => array(
                                    'name' => array(
                                        'single-values' => array('value', 'value2'),
                                    ),
                                ),
                            ),
                        ),
                    )
                ),
                3,
                4,
                0,
                0,
            ),
            array(
                json_encode(
                    array(
                        'groups' => array(
                            array(
                                'groups' => array(
                                    array(
                                        'groups' => array(
                                            array(
                                                'fields' => array(
                                                    'name' => array(
                                                        'single-values' => array('value', 'value2'),
                                                    ),
                                                ),
                                            ),
                                        ),
                                    ),
                                    array(
                                        'groups' => array(
                                            array(
                                                'fields' => array(
                                                    'name' => array(
                                                        'single-values' => array('value', 'value2'),
                                                    ),
                                                ),
                                            ),
                                            array(
                                                'fields' => array(
                                                    'name' => array(
                                                        'single-values' => array('value', 'value2'),
                                                    ),
                                                ),
                                            ),
                                            array(
                                                'fields' => array(
                                                    'name' => array(
                                                        'single-values' => array('value', 'value2'),
                                                    ),
                                                ),
                                            ),
                                            array(
                                                'fields' => array(
                                                    'name' => array(
                                                        'single-values' => array('value', 'value2'),
                                                    ),
                                                ),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    )
                ),
            3,
            4,
            1,
            2,
            ),
        );
    }

    public function provideNestingLevelExceededTests()
    {
        return array(
            array(
                json_encode(
                    array(
                        'groups' => array(
                            array(
                                'groups' => array(
                                    array(
                                        'fields' => array(
                                            'name' => array(
                                                'single-values' => array('value', 'value2'),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    )
                ),
            ),
        );
    }

    public function provideUnknownFieldTests()
    {
        return array(
            array(
                json_encode(
                    array(
                        'fields' => array(
                            'field2' => array(
                                'single-values' => array('value', 'value2'),
                            ),
                        ),
                    )
                ),
            ),
        );
    }

    public function provideUnsupportedValueTypeExceptionTests()
    {
        return array(
            array(
                json_encode(
                    array(
                        'fields' => array(
                            'no-range-field' => array(
                                'ranges' => array(array('lower' => 10, 'upper' => 20)),
                            ),
                        ),

                    )
                ),
                'no-range-field',
                'range',
            ),
            array(
                json_encode(
                    array(
                        'fields' => array(
                            'no-range-field' => array(
                                'excluded-ranges' => array(array('lower' => 10, 'upper' => 20)),
                            ),
                        ),
                    )
                ),
                'no-range-field',
                'range',
            ),
            array(
                json_encode(
                    array(
                        'fields' => array(
                            'no-compares-field' => array(
                                'comparisons' => array(array('value' => 10, 'operator' => '>')),
                            ),
                        ),
                    )
                ),
                'no-compares-field',
                'comparison',
            ),
            array(
                json_encode(
                    array(
                        'fields' => array(
                            'no-matchers-field' => array(
                                'pattern-matchers' => array(array('value' => 'foo', 'type' => 'CONTAINS')),
                            ),
                        ),
                    )
                ),
                'no-matchers-field',
                'pattern-match',
            ),
        );
    }

    public function provideFieldRequiredTests()
    {
        return array(
            array(
                json_encode(
                    array(
                        'fields' => array(
                            'field1' => array(
                                'single-values' => array('value', 'value2'),
                            ),
                        ),
                    )
                ),
                'field2',
                0,
                0,
            ),
            array(
                json_encode(
                    array(
                        'groups' => array(
                            array(
                                'groups' => array(
                                    array(
                                        'fields' => array(
                                            'field1' => array(
                                                'single-values' => array('value', 'value2'),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    )
                ),
                'field2',
                0,
                2,
            ),
            array(
                json_encode(
                    array(
                        'groups' => array(
                            array(
                                'groups' => array(
                                    array(
                                        'fields' => array(
                                            'field2' => array(
                                                'single-values' => array('value'),
                                            ),
                                        ),
                                    ),
                                    array(
                                        'fields' => array(
                                            'field1' => array(
                                                'single-values' => array('value', 'value2'),
                                            ),
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    )
                ),
                'field2',
                1,
                2,
            ),
        );
    }

    /**
     * @return array[]
     */
    public function provideInvalidRangeTests()
    {
        return array(
            array(
                json_encode(
                    array(
                        'fields' => array(
                            'id' => array(
                                'ranges' => array(
                                    array('lower' => 30, 'upper' => 10),
                                    array('lower' => 50, 'upper' => 60),
                                    array('lower' => 40, 'upper' => 20),
                                ),
                            ),
                        ),
                    )
                ),
            ),
            array(
                json_encode(
                    array(
                        'fields' => array(
                            'id' => array(
                                'excluded-ranges' => array(
                                    array('lower' => 30, 'upper' => 10),
                                    array('lower' => 50, 'upper' => 60),
                                    array('lower' => 40, 'upper' => 20),
                                ),
                            ),
                        ),
                    )
                ),
                true,
            ),
        );
    }

    public function provideInvalidValueTests()
    {
        return array(
            array(
                json_encode(
                    array(
                        'fields' => array(
                            'id' => array(
                                'single-values' => array('foo', '30', 'bar'),
                                'comparisons' => array(array('operator' => '>', 'value' => 'life')),
                            ),
                        ),
                    )
                ),
                'id',
                array(
                    new ValuesError('singleValues[0]', 'This value is not valid.'),
                    new ValuesError('singleValues[2]', 'This value is not valid.'),
                    new ValuesError('comparisons[0].value', 'This value is not valid.'),
                ),
            ),
        );
    }
}
