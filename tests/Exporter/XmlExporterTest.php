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

namespace Rollerworks\Component\Search\Tests\Exporter;

use Rollerworks\Component\Search\ConditionExporter;
use Rollerworks\Component\Search\Exporter\XmlExporter;
use Rollerworks\Component\Search\Input\XmlInput;
use Rollerworks\Component\Search\InputProcessor;
use Rollerworks\Component\Search\Test\SearchConditionExporterTestCase;

/**
 * @internal
 */
final class XmlExporterTest extends SearchConditionExporterTestCase
{
    public function provideSingleValuePairTest()
    {
        return
            '<?xml version="1.0" encoding="UTF-8"?'.'>
            <search xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'.
            ' xsi:schemaLocation="http://rollerworks.github.io/search/input/schema/search '.
            'http://rollerworks.github.io/schema/search/xml-input-2.0.xsd" logical="AND">
                <fields>
                    <field name="name">
                        <simple-values>
                            <value>value </value>
                            <value>-value2</value>
                            <value>value2-</value>
                            <value>10.00</value>
                            <value>10,00</value>
                            <value>h&#xCC;</value>
                            <value>&#x664;&#x664;&#x664;&#x666;&#x665;&#x664;&#x666;&#x660;&#x660;</value>
                            <value>doctor"who""</value>
                        </simple-values>
                        <excluded-simple-values>
                            <value>value3</value>
                        </excluded-simple-values>
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
            ' xsi:schemaLocation="http://rollerworks.github.io/search/input/schema/search '.
            'http://rollerworks.github.io/schema/search/xml-input-2.0.xsd" logical="AND">
                <fields>
                    <field name="name">
                        <simple-values>
                            <value>value</value>
                            <value>value2</value>
                        </simple-values>
                    </field>
                    <field name="date">
                        <simple-values>
                            <value>2014-12-16</value>
                        </simple-values>
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
            ' xsi:schemaLocation="http://rollerworks.github.io/search/input/schema/search '.
            'http://rollerworks.github.io/schema/search/xml-input-2.0.xsd" logical="AND">
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
                                <lower>2014-12-16</lower>
                                <upper>2014-12-20</upper>
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
            ' xsi:schemaLocation="http://rollerworks.github.io/search/input/schema/search '.
            'http://rollerworks.github.io/schema/search/xml-input-2.0.xsd" logical="AND">
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
                            <compare operator="&gt;=">2014-12-16</compare>
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
            ' xsi:schemaLocation="http://rollerworks.github.io/search/input/schema/search '.
            'http://rollerworks.github.io/schema/search/xml-input-2.0.xsd" logical="AND">
                <fields>
                <field name="name">
                    <pattern-matchers>
                        <pattern-matcher type="contains" case-insensitive="false">value</pattern-matcher>
                        <pattern-matcher type="starts_with" case-insensitive="true">value2</pattern-matcher>
                        <pattern-matcher type="ends_with" case-insensitive="false">value3</pattern-matcher>
                        <pattern-matcher type="not_contains" case-insensitive="false">value4</pattern-matcher>
                        <pattern-matcher type="not_contains" case-insensitive="true">value5</pattern-matcher>
                        <pattern-matcher type="equals" case-insensitive="false">value9</pattern-matcher>
                        <pattern-matcher type="not_equals" case-insensitive="false">value10</pattern-matcher>
                        <pattern-matcher type="equals" case-insensitive="true">value11</pattern-matcher>
                        <pattern-matcher type="not_equals" case-insensitive="true">value12</pattern-matcher>
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
            ' xsi:schemaLocation="http://rollerworks.github.io/search/input/schema/search '.
            'http://rollerworks.github.io/schema/search/xml-input-2.0.xsd" logical="AND">
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
            </search>'
        ;
    }

    public function provideMultipleSubGroupTest()
    {
        return
            '<?xml version="1.0" encoding="UTF-8"?'.'>
            <search xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'.
            ' xsi:schemaLocation="http://rollerworks.github.io/search/input/schema/search '.
            'http://rollerworks.github.io/schema/search/xml-input-2.0.xsd" logical="AND">
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
            </search>'
        ;
    }

    public function provideNestedGroupTest()
    {
        return
            '<?xml version="1.0" encoding="UTF-8"?'.'>
            <search xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'.
            ' xsi:schemaLocation="http://rollerworks.github.io/search/input/schema/search '.
            'http://rollerworks.github.io/schema/search/xml-input-2.0.xsd" logical="AND">
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
            </search>'
        ;
    }

    protected function assertExportEquals($expected, $actual)
    {
        if (!empty($expected) xor !empty($actual)) {
            self::assertEquals($expected, $actual);
        } else {
            self::assertXmlStringEqualsXmlString($expected, $actual);
        }
    }

    public function provideEmptyValuesTest()
    {
        return '<?xml version="1.0" encoding="UTF-8"?'.'>
            <search xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'.
            ' xsi:schemaLocation="http://rollerworks.github.io/search/input/schema/search '.
            'http://rollerworks.github.io/schema/search/xml-input-2.0.xsd" logical="AND"/>'
        ;
    }

    public function provideEmptyGroupTest()
    {
        return '<?xml version="1.0" encoding="UTF-8"?'.'>
            <search xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'.
            ' xsi:schemaLocation="http://rollerworks.github.io/search/input/schema/search '.
            'http://rollerworks.github.io/schema/search/xml-input-2.0.xsd" logical="AND">
                <groups>
                    <group logical="AND"/>
                </groups>
            </search>'
        ;
    }

    protected function getExporter(): ConditionExporter
    {
        return new XmlExporter();
    }

    protected function getInputProcessor(): InputProcessor
    {
        return new XmlInput();
    }
}
