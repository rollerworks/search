<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests\Input;

use Rollerworks\Component\Search\Input\ProcessorConfig;
use Rollerworks\Component\Search\Input\XmlInput;
use Rollerworks\Component\Search\ValuesError;
use Rollerworks\Component\Search\ValuesGroup;

final class XmlInputTest extends InputProcessorTestCase
{
    protected function getProcessor()
    {
        return new XmlInput($this->fieldAliasResolver->reveal());
    }

    /**
     * @param string $input
     * @param string $message
     *
     * @test
     * @dataProvider provideInvalidInputTests
     */
    public function it_errors_when_the_syntax_is_invalid($input, $message)
    {
        $fieldSet = $this->getFieldSet(false)->add('field1', 'text')->getFieldSet();

        $processor = $this->getProcessor();
        $config = new ProcessorConfig($fieldSet);

        $this->setExpectedException('\InvalidArgumentException', $message);

        $processor->process($config, $input);
    }

    public function provideInvalidInputTests()
    {
        return array(
            array(
                'foobar',
                "[ERROR 4] Start tag expected, '<' not found (in n/a - line 1, column 1)",
            ),
            array(
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>',
                "[ERROR 4] Start tag expected, '<' not found (in n/a - line 1, column 37)",
            ),
            array(
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search>
                    <fields>
                        <field name="name">
                            <single-values>
                                <value>value<></value>
                            </single-values>
                            <excluded-values>
                        </field>
                    </fields>
                </search>',
                "[ERROR 73] expected '>' (in n/a - line 10, column 23)",
            ),
        );
    }

    public function provideEmptyInputTests()
    {
        return array(
            array(''),
            array(' '),
        );
    }

    public function provideSingleValuePairTests()
    {
        return array(
            array(
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search>
                    <fields>
                        <field name="name">
                            <single-values>
                                <value>value</value>
                                <value>value2</value>
                                <value>٤٤٤٦٥٤٦٠٠</value>
                                <value>30</value>
                                <value>30L</value>
                            </single-values>
                            <excluded-values>
                                <value>value3</value>
                            </excluded-values>
                        </field>
                    </fields>
                </search>',
            ),
            array(
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search logical="AND">
                    <fields>
                        <field name="name">
                            <single-values>
                                <value>value</value>
                                <value>value2</value>
                                <value>٤٤٤٦٥٤٦٠٠</value>
                                <value>30</value>
                                <value>30L</value>
                            </single-values>
                            <excluded-values>
                                <value>value3</value>
                            </excluded-values>
                        </field>
                    </fields>
                </search>',
            ),
            array(
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search logical="AND">
                    <fields>
                        <field name="name">
                            <single-values>
                                <value><![CDATA[value]]></value>
                                <value>value2</value>
                                <value>٤٤٤٦٥٤٦٠٠</value>
                                <value>30</value>
                                <value>30L</value>
                            </single-values>
                            <excluded-values>
                                <value>value3</value>
                            </excluded-values>
                        </field>
                    </fields>
                </search>',
            ),
            array(
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'.
                ' xsi:schemaLocation="http://rollerworks.github.io/search/input/schema/search '.
                'http://rollerworks.github.io/schema/search/xml-input-1.0.xsd" logical="AND">
                    <fields>
                        <field name="name">
                            <single-values>
                                <value>value</value>
                            <value>value2</value>
                            <value>٤٤٤٦٥٤٦٠٠</value>
                                <value>30</value>
                                <value>30L</value>
                            </single-values>
                            <excluded-values>
                                <value>value3</value>
                            </excluded-values>
                        </field>
                    </fields>
                </search>',
            ),
        );
    }

    public function provideMultipleValues()
    {
        return array(
            array(
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search>
                    <fields>
                        <field name="name">
                            <single-values>
                                <value>value</value>
                                <value>value2</value>
                            </single-values>
                        </field>
                        <field name="date">
                            <single-values>
                                <value>12-16-2014</value>
                            </single-values>
                        </field>
                    </fields>
                </search>',
            ),
        );
    }

    public function provideRangeValues()
    {
        return array(
            array(
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search>
                    <fields>
                        <field name="id">
                            <ranges>
                                <range>
                                    <lower>1</lower>
                                    <upper>10</upper>
                                </range>
                                <range>
                                    <lower>15</lower>
                                    <upper>30</upper>
                                </range>
                                <range>
                                    <lower inclusive="false">100</lower>
                                    <upper>200</upper>
                                </range>
                                <range>
                                    <lower>310</lower>
                                    <upper inclusive="false">400</upper>
                                </range>
                            </ranges>
                            <excluded-ranges>
                                <range>
                                    <lower>50</lower>
                                    <upper>70</upper>
                                </range>
                            </excluded-ranges>
                        </field>
                        <field name="date">
                            <ranges>
                                <range>
                                    <lower>12-16-2014</lower>
                                    <upper>12-20-2014</upper>
                                </range>
                            </ranges>
                        </field>
                    </fields>
                </search>',
            ),
        );
    }

    public function provideComparisonValues()
    {
        return array(
            array(
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search>
                    <fields>
                        <field name="id">
                            <comparisons>
                                <compare operator="&gt;">1</compare>
                                <compare operator="&lt;">2</compare>
                                <compare operator="&lt;=">5</compare>
                                <compare operator="&gt;=">8</compare>
                                <compare operator="&lt;&gt;">20</compare>
                            </comparisons>
                        </field>
                        <field name="date">
                            <comparisons>
                                <compare operator="&gt;=">12-16-2014</compare>
                            </comparisons>
                        </field>
                    </fields>
                </search>',
            ),
        );
    }

    public function provideMatcherValues()
    {
        return array(
            array(
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search>
                    <fields>
                    <field name="name">
                        <pattern-matchers>
                            <pattern-matcher type="contains" case-insensitive="false">value</pattern-matcher>
                            <pattern-matcher type="starts_with" case-insensitive="true">value2</pattern-matcher>
                            <pattern-matcher type="ends_with" case-insensitive="false">value3</pattern-matcher>
                            <pattern-matcher type="regex" case-insensitive="false">^foo|bar?</pattern-matcher>
                            <pattern-matcher type="not_contains" case-insensitive="false">value4</pattern-matcher>
                            <pattern-matcher type="not_contains" case-insensitive="true">value5</pattern-matcher>
                        </pattern-matchers>
                    </field>
                  </fields>
                </search>',
            ),
        );
    }

    public function provideGroupTests()
    {
        return array(
            array(
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search>
                    <fields>
                        <field name="name">
                            <single-values>
                                <value>value</value>
                                <value>value2</value>
                            </single-values>
                        </field>
                    </fields>
                    <groups>
                        <group logical="AND">
                            <fields>
                                <field name="name">
                                    <single-values>
                                        <value>value3</value>
                                        <value>value4</value>
                                    </single-values>
                                </field>
                            </fields>
                        </group>
                        <group logical="OR">
                            <fields>
                                <field name="name">
                                    <single-values>
                                        <value>value8</value>
                                        <value>value10</value>
                                    </single-values>
                                </field>
                            </fields>
                        </group>
                    </groups>
                </search>',
            ),
            array(
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search>
                    <fields>
                        <field name="name">
                            <single-values>
                                <value>value</value>
                                <value>value2</value>
                            </single-values>
                        </field>
                    </fields>
                    <groups>
                        <group>
                            <fields>
                                <field name="name">
                                    <single-values>
                                        <value>value3</value>
                                        <value>value4</value>
                                    </single-values>
                                </field>
                            </fields>
                        </group>
                        <group logical="OR">
                            <fields>
                                <field name="name">
                                    <single-values>
                                        <value>value8</value>
                                        <value>value10</value>
                                    </single-values>
                                </field>
                            </fields>
                        </group>
                    </groups>
                </search>',
            ),
        );
    }

    public function provideRootLogicalTests()
    {
        return array(
            array(
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search>
                    <fields>
                        <field name="name">
                            <single-values>
                                <value>value</value>
                                <value>value2</value>
                            </single-values>
                        </field>
                    </fields>
                </search>',
            ),
            array(
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search logical="AND">
                    <fields>
                        <field name="name">
                            <single-values>
                                <value>value</value>
                                <value>value2</value>
                            </single-values>
                        </field>
                    </fields>
                </search>',
            ),
            array(
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search logical="OR">
                    <fields>
                        <field name="name">
                            <single-values>
                                <value>value</value>
                                <value>value2</value>
                            </single-values>
                        </field>
                    </fields>
                </search>',
                ValuesGroup::GROUP_LOGICAL_OR,
            ),
        );
    }

    public function provideMultipleSubGroupTests()
    {
        return array(
            array(
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search>
                    <groups>
                        <group logical="AND">
                            <fields>
                                <field name="name">
                                    <single-values>
                                        <value>value</value>
                                        <value>value2</value>
                                    </single-values>
                                </field>
                            </fields>
                        </group>
                        <group logical="AND">
                            <fields>
                                <field name="name">
                                    <single-values>
                                        <value>value3</value>
                                        <value>value4</value>
                                    </single-values>
                                </field>
                            </fields>
                        </group>
                    </groups>
                </search>',
            ),
        );
    }

    public function provideNestedGroupTests()
    {
        return array(
            array(
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search>
                    <groups>
                        <group logical="AND">
                            <groups>
                                <group logical="AND">
                                    <fields>
                                        <field name="name">
                                            <single-values>
                                                <value>value</value>
                                                <value>value2</value>
                                            </single-values>
                                        </field>
                                    </fields>
                                </group>
                            </groups>
                        </group>
                    </groups>
                </search>',
            ),
        );
    }

    public function provideAliasedFieldsTests()
    {
        return array(
            array(
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search>
                    <fields>
                        <field name="name">
                            <single-values>
                                <value>value</value>
                            </single-values>
                        </field>
                        <field name="firstname">
                            <single-values>
                                <value>value2</value>
                            </single-values>
                        </field>
                    </fields>
                </search>',
            ),
            array(
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search>
                    <fields>
                        <field name="firstname">
                            <single-values>
                                <value>value</value>
                            </single-values>
                        </field>
                        <field name="name">
                            <single-values>
                                <value>value2</value>
                            </single-values>
                        </field>
                    </fields>
                </search>',
            ),
        );
    }

    public function provideValueOverflowTests()
    {
        return array(
            array(
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search>
                    <fields>
                        <field name="name">
                            <single-values>
                                <value>value</value>
                                <value>value2</value>
                                <value>value3</value>
                                <value>value4</value>
                                <value>value5</value>
                            </single-values>
                        </field>
                    </fields>
                </search>',
                'name',
                3,
                4,
                0,
                0,
            ),
            array(
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search>
                    <groups>
                        <group logical="AND">
                            <groups>
                                <group logical="AND">
                                    <fields>
                                        <field name="name">
                                            <single-values>
                                                <value>value</value>
                                                <value>value2</value>
                                                <value>value3</value>
                                                <value>value4</value>
                                                <value>value5</value>
                                            </single-values>
                                        </field>
                                    </fields>
                                </group>
                            </groups>
                        </group>
                    </groups>
                </search>',
                'name',
                3,
                4,
                0,
                2,
            ),
            array(
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search>
                    <groups>
                        <group logical="AND">
                            <groups>
                                <group logical="AND">
                                    <fields>
                                        <field name="name">
                                            <single-values>
                                                <value>value</value>
                                            </single-values>
                                        </field>
                                    </fields>
                                </group>
                                <group logical="AND">
                                    <fields>
                                        <field name="name">
                                            <single-values>
                                                <value>value</value>
                                                <value>value2</value>
                                                <value>value3</value>
                                                <value>value4</value>
                                                <value>value5</value>
                                            </single-values>
                                        </field>
                                    </fields>
                                </group>
                            </groups>
                        </group>
                    </groups>
                </search>',
                'name',
                3,
                4,
                1,
                2,
            ),
            // merging
            array(
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search>
                    <fields>
                        <field name="name">
                            <single-values>
                                <value>value</value>
                                <value>value2</value>
                            </single-values>
                        </field>
                        <field name="name">
                            <single-values>
                                <value>value3</value>
                                <value>value4</value>
                                <value>value5</value>
                            </single-values>
                        </field>
                    </fields>
                </search>',
                'name',
                3,
                4,
                0,
                0,
            ),
            array(
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search>
                    <fields>
                        <field name="id">
                            <single-values>
                                <value>1</value>
                                <value>2</value>
                            </single-values>
                        </field>
                        <field name="id">
                            <single-values>
                                <value>3</value>
                                <value>4</value>
                                <value>5</value>
                            </single-values>
                        </field>
                    </fields>
                </search>',
                'id',
                3,
                4,
                0,
                0,
            ),
            array(
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search>
                    <fields>
                        <field name="id">
                            <single-values>
                                <value>1</value>
                                <value>2</value>
                            </single-values>
                        </field>
                        <field name="user-id">
                            <single-values>
                                <value>3</value>
                                <value>4</value>
                                <value>5</value>
                            </single-values>
                        </field>
                    </fields>
                </search>',
                'id',
                3,
                4,
                0,
                0,
            ),
        );
    }

    public function provideGroupsOverflowTests()
    {
        return array(
            array(
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search>
                    <groups>
                        <group logical="AND">
                            <fields>
                                <field name="name">
                                    <single-values>
                                        <value>value</value>
                                        <value>value2</value>
                                    </single-values>
                                </field>
                            </fields>
                        </group>
                        <group logical="AND">
                            <fields>
                                <field name="name">
                                    <single-values>
                                        <value>value</value>
                                        <value>value2</value>
                                    </single-values>
                                </field>
                            </fields>
                        </group>
                        <group logical="AND">
                            <fields>
                                <field name="name">
                                    <single-values>
                                        <value>value</value>
                                        <value>value2</value>
                                    </single-values>
                                </field>
                            </fields>
                        </group>
                        <group logical="AND">
                            <fields>
                                <field name="name">
                                    <single-values>
                                        <value>value</value>
                                        <value>value2</value>
                                    </single-values>
                                </field>
                            </fields>
                        </group>
                    </groups>
                </search>',
                3,
                4,
                0,
                0,
            ),
            array(
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search>
                    <groups>
                        <group logical="AND">
                            <groups>
                                <group logical="AND">
                                    <groups>
                                        <group logical="AND">
                                            <fields>
                                                <field name="name">
                                                    <single-values>
                                                        <value>value</value>
                                                        <value>value2</value>
                                                    </single-values>
                                                </field>
                                            </fields>
                                        </group>
                                    </groups>
                                </group>
                                <group logical="AND">
                                    <groups>
                                        <group logical="AND">
                                            <fields>
                                                <field name="name">
                                                    <single-values>
                                                        <value>value</value>
                                                        <value>value2</value>
                                                    </single-values>
                                                </field>
                                            </fields>
                                        </group>
                                        <group logical="AND">
                                            <fields>
                                                <field name="name">
                                                    <single-values>
                                                        <value>value</value>
                                                        <value>value2</value>
                                                    </single-values>
                                                </field>
                                            </fields>
                                        </group>
                                        <group logical="AND">
                                            <fields>
                                                <field name="name">
                                                    <single-values>
                                                        <value>value</value>
                                                        <value>value2</value>
                                                    </single-values>
                                                </field>
                                            </fields>
                                        </group>
                                        <group logical="AND">
                                            <fields>
                                                <field name="name">
                                                    <single-values>
                                                        <value>value</value>
                                                        <value>value2</value>
                                                    </single-values>
                                                </field>
                                            </fields>
                                        </group>
                                    </groups>
                                </group>
                            </groups>
                        </group>
                    </groups>
                </search>',
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
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search>
                    <groups>
                        <group logical="AND">
                            <groups>
                                <group logical="AND">
                                    <fields>
                                        <field name="field2">
                                            <single-values>
                                                <value>value</value>
                                            </single-values>
                                        </field>
                                    </fields>
                                </group>
                            </groups>
                        </group>
                    </groups>
                </search>',
            ),
        );
    }

    public function provideUnknownFieldTests()
    {
        return array(
            array(
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search>
                    <fields>
                        <field name="field2">
                            <single-values>
                                <value>value</value>
                            </single-values>
                        </field>
                    </fields>
                </search>',
            ),
        );
    }

    public function provideUnsupportedValueTypeExceptionTests()
    {
        return array(
            array(
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search>
                    <fields>
                        <field name="no-range-field">
                            <ranges>
                                <range>
                                    <lower>1</lower>
                                    <upper>12</upper>
                                </range>
                            </ranges>
                        </field>
                    </fields>
                </search>',
                'no-range-field',
                'range',
            ),
            array(
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search>
                    <fields>
                        <field name="no-compares-field">
                            <comparisons>
                                <compare operator="&gt;">12</compare>
                            </comparisons>
                        </field>
                    </fields>
                </search>',
                'no-compares-field',
                'comparison',
            ),
            array(
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search>
                    <fields>
                        <field name="no-matchers-field">
                          <pattern-matchers>
                            <pattern-matcher type="starts_with" case-insensitive="false">12</pattern-matcher>
                          </pattern-matchers>
                        </field>
                      </fields>
                </search>',
                'no-matchers-field',
                'pattern-match',
            ),
        );
    }

    public function provideFieldRequiredTests()
    {
        return array(
            array(
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search>
                    <fields>
                        <field name="field1">
                            <single-values>
                                <value>value</value>
                                <value>value2</value>
                                <value>value3</value>
                                <value>value4</value>
                                <value>value5</value>
                            </single-values>
                        </field>
                    </fields>
                </search>',
                'field2',
                0,
                0,
            ),
            array(
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search>
                    <groups>
                        <group logical="AND">
                            <groups>
                                <group logical="AND">
                                    <fields>
                                        <field name="field1">
                                            <single-values>
                                                <value>value</value>
                                                <value>value2</value>
                                                <value>value3</value>
                                                <value>value4</value>
                                                <value>value5</value>
                                            </single-values>
                                        </field>
                                    </fields>
                                </group>
                            </groups>
                        </group>
                    </groups>
                </search>',
                'field2',
                0,
                2,
            ),
            array(
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search>
                    <groups>
                        <group logical="AND">
                            <groups>
                                <group logical="AND">
                                    <fields>
                                        <field name="field2">
                                            <single-values>
                                                <value>value</value>
                                            </single-values>
                                        </field>
                                    </fields>
                                </group>
                                <group logical="AND">
                                    <fields>
                                        <field name="field1">
                                            <single-values>
                                                <value>value</value>
                                                <value>value2</value>
                                                <value>value3</value>
                                                <value>value4</value>
                                                <value>value5</value>
                                            </single-values>
                                        </field>
                                    </fields>
                                </group>
                            </groups>
                        </group>
                    </groups>
                </search>',
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
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search>
                    <fields>
                        <field name="id">
                            <ranges>
                                <range>
                                    <lower>30</lower>
                                    <upper>10</upper>
                                </range>
                                <range>
                                    <lower>50</lower>
                                    <upper>60</upper>
                                </range>
                                <range>
                                    <lower>40</lower>
                                    <upper>20</upper>
                                </range>
                            </ranges>
                        </field>
                    </fields>
                </search>',
            ),
            array(
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search>
                    <fields>
                        <field name="id">
                            <excluded-ranges>
                                <range>
                                    <lower>30</lower>
                                    <upper>10</upper>
                                </range>
                                <range>
                                    <lower>50</lower>
                                    <upper>60</upper>
                                </range>
                                <range>
                                    <lower>40</lower>
                                    <upper>20</upper>
                                </range>
                            </excluded-ranges>
                        </field>
                    </fields>
                </search>',
                true,
            ),
        );
    }

    public function provideInvalidValueTests()
    {
        return array(
            array(
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search>
                    <fields>
                        <field name="id">
                            <single-values>
                                <value>foo</value>
                                <value>30</value>
                                <value>bar</value>
                            </single-values>
                           <comparisons>
                                <compare operator="&gt;">life</compare>
                            </comparisons>
                        </field>
                    </fields>
                </search>',
                'id',
                array(
                    new ValuesError('singleValues[0]', 'This value is not valid.'),
                    new ValuesError('singleValues[2]', 'This value is not valid.'),
                    new ValuesError('comparisons[0].value', 'This value is not valid.'),
                ),
            ),
            array(
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search>
                    <fields>
                        <field name="id">
                            <excluded-values>
                                <value>foo</value>
                                <value>30</value>
                                <value>bar</value>
                            </excluded-values>
                        </field>
                    </fields>
                </search>',
                'id',
                array(
                    new ValuesError('excludedValues[0]', 'This value is not valid.'),
                    new ValuesError('excludedValues[2]', 'This value is not valid.'),
                ),
            ),
            array(
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search>
                    <fields>
                        <field name="id">
                            <ranges>
                                <range>
                                    <lower>foo</lower>
                                    <upper>10</upper>
                                </range>
                                <range>
                                    <lower>50</lower>
                                    <upper>60</upper>
                                </range>
                                <range>
                                    <lower>50</lower>
                                    <upper>bar</upper>
                                </range>
                            </ranges>
                        </field>
                    </fields>
                </search>',
                'id',
                array(
                    new ValuesError('ranges[0].lower', 'This value is not valid.'),
                    new ValuesError('ranges[2].upper', 'This value is not valid.'),
                ),
            ),
        );
    }

    public function provideNestedErrorsTests()
    {
        return array(
            array(
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search>
                    <fields>
                        <field name="date">
                            <single-values>
                                <value>value</value>
                                <value>value2</value>
                                <value>value3</value>
                                <value>value4</value>
                                <value>value5</value>
                            </single-values>
                        </field>
                    </fields>
                </search>',
            ),
        );
    }
}
