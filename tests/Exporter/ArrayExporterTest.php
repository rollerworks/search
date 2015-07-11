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
        return array(
            'fields' => array(
                'name' => array(
                    'single-values' => array(
                        'value ',
                        '-value2',
                        'value2-',
                        '10.00',
                        '10,00',
                        'hÌ',
                        '٤٤٤٦٥٤٦٠٠',
                        'doctor"who""',
                    ),
                    'excluded-values' => array('value3'),
                ),
            ),
        );
    }

    public function provideFieldAliasTest()
    {
        return array(
            'fields' => array(
                'firstname' => array(
                    'single-values' => array(
                        'value',
                        'value2',
                    ),
                ),
            ),
        );
    }

    public function provideMultipleValuesTest()
    {
        return array(
            'fields' => array(
                'name' => array(
                    'single-values' => array('value', 'value2'),
                ),
                'date' => array(
                    'single-values' => array('12-16-2014'),
                ),
            ),
        );
    }

    public function provideRangeValuesTest()
    {
        return array(
            'fields' => array(
                'id' => array(
                    'ranges' => array(
                        array('lower' => 1, 'upper' => 10),
                        array('lower' => 15, 'upper' => 30),
                        array('lower' => 100, 'upper' => 200, 'inclusive-lower' => false),
                        array('lower' => 310, 'upper' => 400, 'inclusive-upper' => false),
                    ),
                    'excluded-ranges' => array(
                        array('lower' => 50, 'upper' => 70),
                    ),
                ),
                'date' => array(
                    'ranges' => array(
                        array('lower' => '12-16-2014', 'upper' => '12-20-2014'),
                    ),
                ),
            ),
        );
    }

    public function provideComparisonValuesTest()
    {
        return array(
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
        );
    }

    public function provideMatcherValuesTest()
    {
        return array(
            'fields' => array(
                'name' => array(
                    'pattern-matchers' => array(
                        array('type' => 'CONTAINS', 'value' => 'value', 'case-insensitive' => false),
                        array('type' => 'STARTS_WITH', 'value' => 'value2', 'case-insensitive' => true),
                        array('type' => 'ENDS_WITH', 'value' => 'value3', 'case-insensitive' => false),
                        array('type' => 'REGEX', 'value' => '^foo|bar?', 'case-insensitive' => false),
                        array('type' => 'NOT_CONTAINS', 'value' => 'value4', 'case-insensitive' => false),
                        array('type' => 'NOT_CONTAINS', 'value' => 'value5', 'case-insensitive' => true),
                    ),
                ),
            ),
        );
    }

    public function provideGroupTest()
    {
        return array(
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
        );
    }

    public function provideMultipleSubGroupTest()
    {
        return array(
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
        );
    }

    public function provideNestedGroupTest()
    {
        return array(
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
        );
    }

    public function provideEmptyValuesTest()
    {
        return array();
    }

    public function provideEmptyGroupTest()
    {
        return array('groups' => array(array()));
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
