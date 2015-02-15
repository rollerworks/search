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

use Rollerworks\Component\Search\Exporter\XmlExporter;
use Rollerworks\Component\Search\ExporterInterface;
use Rollerworks\Component\Search\Input\XmlInput;
use Rollerworks\Component\Search\InputProcessorInterface;

final class XmlExporterTest extends SearchConditionExporterTestCase
{
    public function provideSingleValuePairTest()
    {
        return
            '<?xml version="1.0" encoding="UTF-8"?'.'>
            <search xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'.
            ' xsi:schemaLocation="http://rollerworks.github.io/search/schema/dic/search '.
            'http://rollerworks.github.io/search/schema/dic/search/input-1.0.xsd" logical="AND">
                <fields>
                    <field name="name">
                        <single-values>
                            <value>value </value>
                            <value>-value2</value>
                            <value>value2-</value>
                            <value>10.00</value>
                            <value>10,00</value>
                            <value>h&#xCC;</value>
                            <value>&#x664;&#x664;&#x664;&#x666;&#x665;&#x664;&#x666;&#x660;&#x660;</value>
                            <value>doctor"who""</value>
                        </single-values>
                        <excluded-values>
                            <value>value3</value>
                        </excluded-values>
                    </field>
                </fields>
            </search>'
        ;
    }

    public function provideFieldAliasTest()
    {
        return
            '<?xml version="1.0" encoding="UTF-8"?'.'>
            <search xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'.
            ' xsi:schemaLocation="http://rollerworks.github.io/search/schema/dic/search '.
            'http://rollerworks.github.io/search/schema/dic/search/input-1.0.xsd" logical="AND">
                <fields>
                    <field name="firstname">
                        <single-values>
                            <value>value</value>
                            <value>value2</value>
                        </single-values>
                    </field>
                </fields>
            </search>'
        ;
    }

    public function provideMultipleValuesTest()
    {
        return
            '<?xml version="1.0" encoding="UTF-8"?'.'>
            <search xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'.
            ' xsi:schemaLocation="http://rollerworks.github.io/search/schema/dic/search '.
            'http://rollerworks.github.io/search/schema/dic/search/input-1.0.xsd" logical="AND">
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
            </search>'
        ;
    }

    public function provideRangeValuesTest()
    {
        return
            '<?xml version="1.0" encoding="UTF-8"?'.'>
            <search xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'.
            ' xsi:schemaLocation="http://rollerworks.github.io/search/schema/dic/search '.
            'http://rollerworks.github.io/search/schema/dic/search/input-1.0.xsd" logical="AND">
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
            </search>'
        ;
    }

    public function provideComparisonValuesTest()
    {
        return
            '<?xml version="1.0" encoding="UTF-8"?'.'>
            <search xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'.
            ' xsi:schemaLocation="http://rollerworks.github.io/search/schema/dic/search '.
            'http://rollerworks.github.io/search/schema/dic/search/input-1.0.xsd" logical="AND">
                <fields>
                    <field name="id">
                        <comparisons>
                            <compare operator="&gt;">1</compare>
                            <compare operator="&lt;">2</compare>
                            <compare operator="&lt;=">5</compare>
                            <compare operator="&gt;=">8</compare>
                        </comparisons>
                    </field>
                    <field name="date">
                        <comparisons>
                            <compare operator="&gt;=">12-16-2014</compare>
                        </comparisons>
                    </field>
                </fields>
            </search>'
        ;
    }

    public function provideMatcherValuesTest()
    {
        return
            '<?xml version="1.0" encoding="UTF-8"?'.'>
            <search xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'.
            ' xsi:schemaLocation="http://rollerworks.github.io/search/schema/dic/search '.
            'http://rollerworks.github.io/search/schema/dic/search/input-1.0.xsd" logical="AND">
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
            </search>'
        ;
    }

    public function provideGroupTest()
    {
        return
            '<?xml version="1.0" encoding="UTF-8"?'.'>
            <search xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'.
            ' xsi:schemaLocation="http://rollerworks.github.io/search/schema/dic/search '.
            'http://rollerworks.github.io/search/schema/dic/search/input-1.0.xsd" logical="AND">
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
            </search>'
        ;
    }

    public function provideMultipleSubGroupTest()
    {
        return
            '<?xml version="1.0" encoding="UTF-8"?'.'>
            <search xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'.
            ' xsi:schemaLocation="http://rollerworks.github.io/search/schema/dic/search '.
            'http://rollerworks.github.io/search/schema/dic/search/input-1.0.xsd" logical="AND">
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
            </search>'
        ;
    }

    public function provideNestedGroupTest()
    {
        return
            '<?xml version="1.0" encoding="UTF-8"?'.'>
            <search xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'.
            ' xsi:schemaLocation="http://rollerworks.github.io/search/schema/dic/search '.
            'http://rollerworks.github.io/search/schema/dic/search/input-1.0.xsd" logical="AND">
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
            </search>'
        ;
    }

    protected function assertExportEquals($expected, $actual)
    {
        if (!empty($expected) xor !empty($actual)) {
            $this->assertEquals($expected, $actual);
        } else {
            $this->assertXmlStringEqualsXmlString($expected, $actual);
        }
    }

    public function provideEmptyValuesTest()
    {
        return '<?xml version="1.0" encoding="UTF-8"?'.'>
            <search xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'.
            ' xsi:schemaLocation="http://rollerworks.github.io/search/schema/dic/search '.
            'http://rollerworks.github.io/search/schema/dic/search/input-1.0.xsd" logical="AND"/>'
        ;
    }

    public function provideEmptyGroupTest()
    {
        return '<?xml version="1.0" encoding="UTF-8"?'.'>
            <search xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'.
            ' xsi:schemaLocation="http://rollerworks.github.io/search/schema/dic/search '.
            'http://rollerworks.github.io/search/schema/dic/search/input-1.0.xsd" logical="AND">
                <groups>
                    <group logical="AND"/>
                </groups>
            </search>'
        ;
    }

    /**
     * @return ExporterInterface
     */
    protected function getExporter()
    {
        return new XmlExporter($this->fieldLabelResolver->reveal());
    }

    /**
     * @return InputProcessorInterface
     */
    protected function getInputProcessor()
    {
        return new XmlInput($this->fieldAliasResolver->reveal());
    }
}
