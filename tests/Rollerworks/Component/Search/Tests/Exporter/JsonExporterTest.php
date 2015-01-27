<?php

/*
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests\Exporter;

use Rollerworks\Component\Search\Exporter\JsonExporter;
use Rollerworks\Component\Search\ExporterInterface;
use Rollerworks\Component\Search\Input\JsonInput;
use Rollerworks\Component\Search\InputProcessorInterface;

final class JsonExporterTest extends SearchConditionExporterTestCase
{
    public function provideSingleValuePairTest()
    {
        return json_encode(
            array(
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
            )
        );
    }

    public function provideMultipleValuesTest()
    {
        return json_encode(
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
        );
    }

    public function provideRangeValuesTest()
    {
        return json_encode(
            array(
                'fields' => array(
                    'id' => array(
                        'ranges' => array(
                            array('lower' => '1', 'upper' => '10'),
                            array('lower' => '15', 'upper' => '30'),
                            array('lower' => '100', 'upper' => '200', 'inclusive-lower' => false),
                            array('lower' => '310', 'upper' => '400', 'inclusive-upper' => false),
                        ),
                        'excluded-ranges' => array(
                            array('lower' => '50', 'upper' => '70'),
                        ),
                    ),
                    'date' => array(
                        'ranges' => array(
                            array('lower' => '12-16-2014', 'upper' => '12-20-2014'),
                        ),
                    ),
                ),
            )
        );
    }

    public function provideComparisonValuesTest()
    {
        return json_encode(
            array(
                'fields' => array(
                    'id' => array(
                        'comparisons' => array(
                            array('operator' => '>', 'value' => '1'),
                            array('operator' => '<', 'value' => '2'),
                            array('operator' => '<=', 'value' => '5'),
                            array('operator' => '>=', 'value' => '8'),
                        ),
                    ),
                    'date' => array(
                        'comparisons' => array(
                            array('operator' => '>=', 'value' => '12-16-2014'),
                        ),
                    ),
                ),
            )
        );
    }

    public function provideMatcherValuesTest()
    {
        return json_encode(
            array(
                'fields' => array(
                    'name' => array(
                        'pattern-matchers' => array(
                            array('type' => 'CONTAINS', 'value' => 'value'),
                            array('type' => 'STARTS_WITH', 'value' => 'value2', 'case-insensitive' => true),
                            array('type' => 'ENDS_WITH', 'value' => 'value3'),
                            array('type' => 'REGEX', 'value' => '^foo|bar?'),
                            array('type' => 'NOT_CONTAINS', 'value' => 'value4'),
                            array('type' => 'NOT_CONTAINS', 'value' => 'value5', 'case-insensitive' => true),
                        ),
                    ),
                ),
            )
        );
    }

    public function provideGroupTest()
    {
        return json_encode(
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
                        'fields' => array(
                            'name' => array(
                                'single-values' => array('value8', 'value10'),
                            ),
                        ),
                        'logical-case' => 'OR',
                    ),
                ),
            )
        );
    }

    public function provideMultipleSubGroupTest()
    {
        return json_encode(
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
        );
    }

    public function provideNestedGroupTest()
    {
        return json_encode(
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
        );
    }

    /**
     * @return ExporterInterface
     */
    protected function getExporter()
    {
        return new JsonExporter($this->fieldAliasResolver->reveal());
    }

    /**
     * @return InputProcessorInterface
     */
    protected function getInputProcessor()
    {
        return new JsonInput($this->fieldAliasResolver->reveal());
    }
}
