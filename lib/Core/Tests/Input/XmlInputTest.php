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
use Rollerworks\Component\Search\Exception\InvalidSearchConditionException;
use Rollerworks\Component\Search\Extension\Core\Type\TextType;
use Rollerworks\Component\Search\Input\ProcessorConfig;
use Rollerworks\Component\Search\Input\XmlInput;
use Rollerworks\Component\Search\InputProcessor;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\ValuesGroup;

/**
 * @internal
 */
final class XmlInputTest extends InputProcessorTestCase
{
    protected function getProcessor(): InputProcessor
    {
        return new XmlInput();
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
        $fieldSet = $this->getFieldSet(false)->add('field1', TextType::class)->getFieldSet();
        $config = new ProcessorConfig($fieldSet);

        $processor = $this->getProcessor();

        try {
            $processor->process($config, $input);

            $this->fail('Condition should be invalid.');
        } catch (\Exception $e) {
            /* @var InvalidSearchConditionException $e */
            self::detectSystemException($e);
            self::assertInstanceOf(InvalidSearchConditionException::class, $e);
            self::assertCount(1, $errors = $e->getErrors());
            self::assertContains($message, current($errors)->message);
            self::assertNotNull(current($errors)->cause);
        }
    }

    public function provideInvalidInputTests()
    {
        return [
            [
                'foobar',
                '[ERROR 4]',
            ],
            [
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>',
                '[ERROR 4]',
            ],
            [
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
                '[ERROR 73]',
            ],
        ];
    }

    public function provideEmptyInputTests()
    {
        return [
            [''],
            [' '],
        ];
    }

    public function provideSingleValuePairTests()
    {
        return [
            [
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search>
                    <fields>
                        <field name="name">
                            <simple-values>
                                <value>value</value>
                                <value>value2</value>
                                <value>٤٤٤٦٥٤٦٠٠</value>
                                <value>30</value>
                                <value>30L</value>
                            </simple-values>
                            <excluded-simple-values>
                                <value>value3</value>
                            </excluded-simple-values>
                        </field>
                    </fields>
                </search>',
            ],
            [
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search logical="AND">
                    <fields>
                        <field name="name">
                            <simple-values>
                                <value>value</value>
                                <value>value2</value>
                                <value>٤٤٤٦٥٤٦٠٠</value>
                                <value>30</value>
                                <value>30L</value>
                            </simple-values>
                            <excluded-simple-values>
                                <value>value3</value>
                            </excluded-simple-values>
                        </field>
                    </fields>
                </search>',
            ],
            [
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search logical="AND">
                    <fields>
                        <field name="name">
                            <simple-values>
                                <value><![CDATA[value]]></value>
                                <value>value2</value>
                                <value>٤٤٤٦٥٤٦٠٠</value>
                                <value>30</value>
                                <value>30L</value>
                            </simple-values>
                            <excluded-simple-values>
                                <value>value3</value>
                            </excluded-simple-values>
                        </field>
                    </fields>
                </search>',
            ],
            [
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'.
                ' xsi:schemaLocation="http://rollerworks.github.io/search/input/schema/search '.
                'http://rollerworks.github.io/schema/search/xml-input-1.0.xsd" logical="AND">
                    <fields>
                        <field name="name">
                            <simple-values>
                                <value>value</value>
                            <value>value2</value>
                            <value>٤٤٤٦٥٤٦٠٠</value>
                                <value>30</value>
                                <value>30L</value>
                            </simple-values>
                            <excluded-simple-values>
                                <value>value3</value>
                            </excluded-simple-values>
                        </field>
                    </fields>
                </search>',
            ],
        ];
    }

    public function provideMultipleValues()
    {
        return [
            [
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search>
                    <fields>
                        <field name="name">
                            <simple-values>
                                <value>value</value>
                                <value>value2</value>
                            </simple-values>
                        </field>
                        <field name="date">
                            <simple-values>
                                <value>2014-12-16T00:00:00Z</value>
                            </simple-values>
                        </field>
                    </fields>
                </search>',
            ],
        ];
    }

    public function provideRangeValues()
    {
        return [
            [
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
                                    <lower>2014-12-16T00:00:00Z</lower>
                                    <upper>2014-12-20T00:00:00Z</upper>
                                </range>
                            </ranges>
                        </field>
                    </fields>
                </search>',
            ],
        ];
    }

    public function provideComparisonValues()
    {
        return [
            [
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
                                <compare operator="&gt;=">2014-12-16T00:00:00Z</compare>
                            </comparisons>
                        </field>
                    </fields>
                </search>',
            ],
        ];
    }

    public function provideMatcherValues()
    {
        return [
            [
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search>
                    <fields>
                    <field name="name">
                        <pattern-matchers>
                            <pattern-matcher type="contains" case-insensitive="false">value</pattern-matcher>
                            <pattern-matcher type="starts_with" case-insensitive="true">value2</pattern-matcher>
                            <pattern-matcher type="ends_with" case-insensitive="false">value3</pattern-matcher>
                            <pattern-matcher type="not_contains" case-insensitive="false">value4</pattern-matcher>
                            <pattern-matcher type="not_contains" case-insensitive="true">value5</pattern-matcher>
                            <pattern-matcher type="equals">value9</pattern-matcher>
                            <pattern-matcher type="not_equals">value10</pattern-matcher>
                            <pattern-matcher type="equals" case-insensitive="true">value11</pattern-matcher>
                            <pattern-matcher type="not_equals" case-insensitive="true">value12</pattern-matcher>
                        </pattern-matchers>
                    </field>
                  </fields>
                </search>',
            ],
        ];
    }

    public function provideGroupTests()
    {
        return [
            [
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search>
                    <fields>
                        <field name="name">
                            <simple-values>
                                <value>value</value>
                                <value>value2</value>
                            </simple-values>
                        </field>
                    </fields>
                    <groups>
                        <group logical="AND">
                            <fields>
                                <field name="name">
                                    <simple-values>
                                        <value>value3</value>
                                        <value>value4</value>
                                    </simple-values>
                                </field>
                            </fields>
                        </group>
                        <group logical="OR">
                            <fields>
                                <field name="name">
                                    <simple-values>
                                        <value>value8</value>
                                        <value>value10</value>
                                    </simple-values>
                                </field>
                            </fields>
                        </group>
                    </groups>
                </search>',
            ],
            [
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search>
                    <fields>
                        <field name="name">
                            <simple-values>
                                <value>value</value>
                                <value>value2</value>
                            </simple-values>
                        </field>
                    </fields>
                    <groups>
                        <group>
                            <fields>
                                <field name="name">
                                    <simple-values>
                                        <value>value3</value>
                                        <value>value4</value>
                                    </simple-values>
                                </field>
                            </fields>
                        </group>
                        <group logical="OR">
                            <fields>
                                <field name="name">
                                    <simple-values>
                                        <value>value8</value>
                                        <value>value10</value>
                                    </simple-values>
                                </field>
                            </fields>
                        </group>
                    </groups>
                </search>',
            ],
        ];
    }

    public function provideRootLogicalTests()
    {
        return [
            [
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search>
                    <fields>
                        <field name="name">
                            <simple-values>
                                <value>value</value>
                                <value>value2</value>
                            </simple-values>
                        </field>
                    </fields>
                </search>',
            ],
            [
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search logical="AND">
                    <fields>
                        <field name="name">
                            <simple-values>
                                <value>value</value>
                                <value>value2</value>
                            </simple-values>
                        </field>
                    </fields>
                </search>',
            ],
            [
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search logical="OR">
                    <fields>
                        <field name="name">
                            <simple-values>
                                <value>value</value>
                                <value>value2</value>
                            </simple-values>
                        </field>
                    </fields>
                </search>',
                ValuesGroup::GROUP_LOGICAL_OR,
            ],
        ];
    }

    public function provideMultipleSubGroupTests()
    {
        return [
            [
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search>
                    <groups>
                        <group logical="AND">
                            <fields>
                                <field name="name">
                                    <simple-values>
                                        <value>value</value>
                                        <value>value2</value>
                                    </simple-values>
                                </field>
                            </fields>
                        </group>
                        <group logical="AND">
                            <fields>
                                <field name="name">
                                    <simple-values>
                                        <value>value3</value>
                                        <value>value4</value>
                                    </simple-values>
                                </field>
                            </fields>
                        </group>
                    </groups>
                </search>',
            ],
        ];
    }

    public function provideNestedGroupTests()
    {
        return [
            [
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search>
                    <groups>
                        <group logical="AND">
                            <groups>
                                <group logical="AND">
                                    <fields>
                                        <field name="name">
                                            <simple-values>
                                                <value>value</value>
                                                <value>value2</value>
                                            </simple-values>
                                        </field>
                                    </fields>
                                </group>
                            </groups>
                        </group>
                    </groups>
                </search>',
            ],
        ];
    }

    public function provideValueOverflowTests()
    {
        return [
            [
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search>
                    <fields>
                        <field name="name">
                            <simple-values>
                                <value>value</value>
                                <value>value2</value>
                                <value>value3</value>
                                <value>value4</value>
                                <value>value5</value>
                            </simple-values>
                        </field>
                    </fields>
                </search>',
                'name',
                "/search/fields/field[@name='name'][1]/simple-values/value[4]",
            ],
            [
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search>
                    <groups>
                        <group logical="AND">
                            <groups>
                                <group logical="AND">
                                    <fields>
                                        <field name="name">
                                            <simple-values>
                                                <value>value</value>
                                                <value>value2</value>
                                                <value>value3</value>
                                                <value>value4</value>
                                                <value>value5</value>
                                            </simple-values>
                                        </field>
                                    </fields>
                                </group>
                            </groups>
                        </group>
                    </groups>
                </search>',
                'name',
                "/search/groups/group[1]/groups/group[1]/fields/field[@name='name'][1]/simple-values/value[4]",
            ],
            [
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search>
                    <groups>
                        <group logical="AND">
                            <groups>
                                <group logical="AND">
                                    <fields>
                                        <field name="name">
                                            <simple-values>
                                                <value>value</value>
                                            </simple-values>
                                        </field>
                                    </fields>
                                </group>
                                <group logical="AND">
                                    <fields>
                                        <field name="name">
                                            <simple-values>
                                                <value>value</value>
                                                <value>value2</value>
                                                <value>value3</value>
                                                <value>value4</value>
                                                <value>value5</value>
                                            </simple-values>
                                        </field>
                                    </fields>
                                </group>
                            </groups>
                        </group>
                    </groups>
                </search>',
                'name',
                "/search/groups/group[1]/groups/group[2]/fields/field[@name='name'][1]/simple-values/value[4]",
            ],
        ];
    }

    public function provideGroupsOverflowTests()
    {
        return [
            [
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search>
                    <groups>
                        <group logical="AND">
                            <fields>
                                <field name="name">
                                    <simple-values>
                                        <value>value</value>
                                        <value>value2</value>
                                    </simple-values>
                                </field>
                            </fields>
                        </group>
                        <group logical="AND">
                            <fields>
                                <field name="name">
                                    <simple-values>
                                        <value>value</value>
                                        <value>value2</value>
                                    </simple-values>
                                </field>
                            </fields>
                        </group>
                        <group logical="AND">
                            <fields>
                                <field name="name">
                                    <simple-values>
                                        <value>value</value>
                                        <value>value2</value>
                                    </simple-values>
                                </field>
                            </fields>
                        </group>
                        <group logical="AND">
                            <fields>
                                <field name="name">
                                    <simple-values>
                                        <value>value</value>
                                        <value>value2</value>
                                    </simple-values>
                                </field>
                            </fields>
                        </group>
                    </groups>
                </search>',
                '/search/groups',
            ],
            [
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
                                                    <simple-values>
                                                        <value>value</value>
                                                        <value>value2</value>
                                                    </simple-values>
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
                                                    <simple-values>
                                                        <value>value</value>
                                                        <value>value2</value>
                                                    </simple-values>
                                                </field>
                                            </fields>
                                        </group>
                                        <group logical="AND">
                                            <fields>
                                                <field name="name">
                                                    <simple-values>
                                                        <value>value</value>
                                                        <value>value2</value>
                                                    </simple-values>
                                                </field>
                                            </fields>
                                        </group>
                                        <group logical="AND">
                                            <fields>
                                                <field name="name">
                                                    <simple-values>
                                                        <value>value</value>
                                                        <value>value2</value>
                                                    </simple-values>
                                                </field>
                                            </fields>
                                        </group>
                                        <group logical="AND">
                                            <fields>
                                                <field name="name">
                                                    <simple-values>
                                                        <value>value</value>
                                                        <value>value2</value>
                                                    </simple-values>
                                                </field>
                                            </fields>
                                        </group>
                                    </groups>
                                </group>
                            </groups>
                        </group>
                    </groups>
                </search>',
                '/search/groups/group[1]/groups/group[2]/groups',
            ],
        ];
    }

    public function provideNestingLevelExceededTests()
    {
        return [
            [
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search>
                    <groups>
                        <group logical="AND">
                            <groups>
                                <group logical="AND">
                                    <fields>
                                        <field name="field2">
                                            <simple-values>
                                                <value>value</value>
                                            </simple-values>
                                        </field>
                                    </fields>
                                </group>
                            </groups>
                        </group>
                    </groups>
                </search>',
                '/search/groups/group[1]/groups/group[1]',
            ],
        ];
    }

    public function provideUnknownFieldTests()
    {
        return [
            [
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search>
                    <fields>
                        <field name="field2">
                            <simple-values>
                                <value>value</value>
                            </simple-values>
                        </field>
                    </fields>
                </search>',
            ],
        ];
    }

    public function provideUnsupportedValueTypeExceptionTests()
    {
        return [
            [
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
                Range::class,
            ],
            [
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
                Compare::class,
            ],
            [
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
                ["/search/fields/field[@name='id'][1]/ranges/range[1]", "/search/fields/field[@name='id'][1]/ranges/range[3]"],
            ],
            [
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
                ["/search/fields/field[@name='id'][1]/excluded-ranges/range[1]", "/search/fields/field[@name='id'][1]/excluded-ranges/range[3]"],
            ],
        ];
    }

    public function provideInvalidValueTests()
    {
        return [
            [
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search>
                    <fields>
                        <field name="id">
                            <simple-values>
                                <value>foo</value>
                                <value>30</value>
                                <value>bar</value>
                            </simple-values>
                           <comparisons>
                                <compare operator="&gt;">life</compare>
                            </comparisons>
                        </field>
                    </fields>
                </search>',
                [
                    new ConditionErrorMessage("/search/fields/field[@name='id'][1]/simple-values/value[1]", 'This value is not valid.'),
                    new ConditionErrorMessage("/search/fields/field[@name='id'][1]/simple-values/value[3]", 'This value is not valid.'),
                    new ConditionErrorMessage("/search/fields/field[@name='id'][1]/comparisons/compare[1]", 'This value is not valid.'),
                ],
            ],
            [
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search>
                    <fields>
                        <field name="id">
                            <excluded-simple-values>
                                <value>foo</value>
                                <value>30</value>
                                <value>bar</value>
                            </excluded-simple-values>
                        </field>
                    </fields>
                </search>',
                [
                    new ConditionErrorMessage("/search/fields/field[@name='id'][1]/excluded-simple-values/value[1]", 'This value is not valid.'),
                    new ConditionErrorMessage("/search/fields/field[@name='id'][1]/excluded-simple-values/value[3]", 'This value is not valid.'),
                ],
            ],
            [
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
                [
                    new ConditionErrorMessage("/search/fields/field[@name='id'][1]/ranges/range[1]/lower", 'This value is not valid.'),
                    new ConditionErrorMessage("/search/fields/field[@name='id'][1]/ranges/range[3]/upper", 'This value is not valid.'),
                ],
            ],
        ];
    }

    public function provideNestedErrorsTests()
    {
        return [
            [
                '<?xml version="1.0" encoding="UTF-8"'.'?'.'>
                <search>
                    <groups>
                        <group>
                            <groups>
                                <group>
                                    <fields>
                                        <field name="date">
                                            <simple-values>
                                                <value>value</value>
                                            </simple-values>
                                        </field>
                                    </fields>
                                </group>
                                <group>
                                    <fields>
                                        <field name="date">
                                            <simple-values>
                                                <value>value</value>
                                                <value>value2</value>
                                            </simple-values>
                                        </field>
                                    </fields>
                                </group>
                            </groups>
                        </group>
                    </groups>
                </search>',
                [
                    new ConditionErrorMessage("/search/groups/group[1]/groups/group[1]/fields/field[@name='date'][1]/simple-values/value[1]", 'This value is not valid.'),
                    new ConditionErrorMessage("/search/groups/group[1]/groups/group[2]/fields/field[@name='date'][1]/simple-values/value[1]", 'This value is not valid.'),
                    new ConditionErrorMessage("/search/groups/group[1]/groups/group[2]/fields/field[@name='date'][1]/simple-values/value[2]", 'This value is not valid.'),
                ],
            ],
        ];
    }
}
