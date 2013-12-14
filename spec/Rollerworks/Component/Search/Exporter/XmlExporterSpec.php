<?php

/*
 * This file is part of the RollerworksRecordFilterBundle package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\Rollerworks\Component\Search\Exporter;

use PhpSpec\ObjectBehavior;
use Rollerworks\Component\Search\FieldConfigInterface;
use Rollerworks\Component\Search\FieldLabelResolverInterface;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\SingleValue;
use Rollerworks\Component\Search\ValuesBag;
use Rollerworks\Component\Search\ValuesGroup;

class XmlExporterSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Rollerworks\Component\Search\Exporter\XmlExporter');
        $this->shouldImplement('Rollerworks\Component\Search\ExporterInterface');
    }

    function it_exports_single_values(FieldSet $fieldSet, FieldConfigInterface $field)
    {
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->get('field1')->willReturn($field);

        $values = new ValuesBag();
        $values->addSingleValue(new SingleValue('', 'value'));
        $values->addSingleValue(new SingleValue('', 'value2'));

        $group = new ValuesGroup();
        $group->addField('field1', $values);

        $condition = new SearchCondition($fieldSet->getWrappedObject(), $group);

        $this->exportCondition($condition)->shouldEqualXmlDocument('<?xml version="1.0" encoding="UTF-8"'.'?'.'>
<search xmlns="http://rollerworks.github.io/search/schema/dic/search"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="http://rollerworks.github.io/search/schema/dic/search http://rollerworks.github.io/search/schema/dic/search/input-1.0.xsd" logical="AND">
  <fields>
    <field name="field1">
      <single-values>
        <value>value</value>
        <value>value2</value>
      </single-values>
    </field>
  </fields>
</search>');
    }

    function it_exports_values_escaped_when_needed(FieldSet $fieldSet, FieldConfigInterface $field, FieldConfigInterface $field2)
    {
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->get('field1')->willReturn($field);

        $fieldSet->has('field2')->willReturn(true);
        $fieldSet->get('field2')->willReturn($field);

        $values = new ValuesBag();
        $values->addSingleValue(new SingleValue('', 'hÌ'));
        $values->addSingleValue(new SingleValue('', '٤٤٤٦٥٤٦٠٠'));
        $values->addSingleValue(new SingleValue('', '<xss>hacked</xss>'));
        $values->addSingleValue(new SingleValue('', 'doctor"who""'));

        $group = new ValuesGroup();
        $group->addField('field1', $values);

        $condition = new SearchCondition($fieldSet->getWrappedObject(), $group);

        $this->exportCondition($condition)->shouldEqualXmlDocument('<?xml version="1.0" encoding="UTF-8"'.'?'.'>
<search xmlns="http://rollerworks.github.io/search/schema/dic/search"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="http://rollerworks.github.io/search/schema/dic/search http://rollerworks.github.io/search/schema/dic/search/input-1.0.xsd" logical="AND">
  <fields>
    <field name="field1">
      <single-values>
        <value>hÌ</value>
        <value>٤٤٤٦٥٤٦٠٠</value>
        <value>&lt;xss&gt;hacked&lt;/xss&gt;</value>
        <value>doctor"who""</value>
      </single-values>
    </field>
  </fields>
</search>');
    }

    function it_exports_excluded_values(FieldSet $fieldSet, FieldConfigInterface $field)
    {
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->get('field1')->willReturn($field);

        $values = new ValuesBag();
        $values->addExcludedValue(new SingleValue('', 'value'));
        $values->addExcludedValue(new SingleValue('', 'value2'));

        $group = new ValuesGroup();
        $group->addField('field1', $values);

        $condition = new SearchCondition($fieldSet->getWrappedObject(), $group);

        $this->exportCondition($condition)->shouldEqualXmlDocument('<?xml version="1.0" encoding="UTF-8"'.'?'.'>
<search xmlns="http://rollerworks.github.io/search/schema/dic/search"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="http://rollerworks.github.io/search/schema/dic/search http://rollerworks.github.io/search/schema/dic/search/input-1.0.xsd" logical="AND">
  <fields>
    <field name="field1">
      <excluded-values>
        <value>value</value>
        <value>value2</value>
      </excluded-values>
    </field>
  </fields>
</search>');
    }

    function it_exports_ranges(FieldSet $fieldSet, FieldConfigInterface $field)
    {
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->get('field1')->willReturn($field);

        $values = new ValuesBag();
        $values->addRange(new Range('', '', true, true, '10', '20'));
        $values->addRange(new Range('', '', true, true, '30', '50'));

        $values->addRange(new Range('', '', true, false, '30', '50'));
        $values->addRange(new Range('', '', false, true, '30', '50'));

        $group = new ValuesGroup();
        $group->addField('field1', $values);

        $condition = new SearchCondition($fieldSet->getWrappedObject(), $group);

        $this->exportCondition($condition)->shouldEqualXmlDocument('<?xml version="1.0" encoding="UTF-8"'.'?'.'>
<search xmlns="http://rollerworks.github.io/search/schema/dic/search"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="http://rollerworks.github.io/search/schema/dic/search http://rollerworks.github.io/search/schema/dic/search/input-1.0.xsd" logical="AND">
  <fields>
    <field name="field1">
      <ranges>
        <range>
            <lower>10</lower>
            <upper>20</upper>
        </range>
        <range>
            <lower>30</lower>
            <upper>50</upper>
        </range>
        <range>
            <lower>30</lower>
            <upper inclusive="false">50</upper>
        </range>
        <range>
            <lower inclusive="false">30</lower>
            <upper>50</upper>
        </range>
      </ranges>
    </field>
  </fields>
</search>');
    }

    function it_exports_excluded_ranges(FieldSet $fieldSet, FieldConfigInterface $field)
    {
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->get('field1')->willReturn($field);

        $values = new ValuesBag();
        $values->addExcludedRange(new Range('', '', true, true, '10', '20'));
        $values->addExcludedRange(new Range('', '', true, true, '30', '50'));

        $group = new ValuesGroup();
        $group->addField('field1', $values);

        $condition = new SearchCondition($fieldSet->getWrappedObject(), $group);

        $this->exportCondition($condition)->shouldEqualXmlDocument('<?xml version="1.0" encoding="UTF-8"'.'?'.'>
<search xmlns="http://rollerworks.github.io/search/schema/dic/search"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="http://rollerworks.github.io/search/schema/dic/search http://rollerworks.github.io/search/schema/dic/search/input-1.0.xsd" logical="AND">
  <fields>
    <field name="field1">
      <excluded-ranges>
        <range>
            <lower>10</lower>
            <upper>20</upper>
        </range>
        <range>
            <lower>30</lower>
            <upper>50</upper>
        </range>
      </excluded-ranges>
    </field>
  </fields>
</search>');
    }

    function it_exports_comparisons(FieldSet $fieldSet, FieldConfigInterface $field)
    {
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->get('field1')->willReturn($field);

        $values = new ValuesBag();
        $values->addComparison(new Compare('', '>', '10'));
        $values->addComparison(new Compare('', '<', '50'));

        $group = new ValuesGroup();
        $group->addField('field1', $values);

        $condition = new SearchCondition($fieldSet->getWrappedObject(), $group);

        $this->exportCondition($condition)->shouldEqualXmlDocument('<?xml version="1.0" encoding="UTF-8"'.'?'.'>
<search xmlns="http://rollerworks.github.io/search/schema/dic/search"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="http://rollerworks.github.io/search/schema/dic/search http://rollerworks.github.io/search/schema/dic/search/input-1.0.xsd" logical="AND">
  <fields>
    <field name="field1">
      <comparisons>
        <compare operator="&gt;">10</compare>
        <compare operator="&lt;">50</compare>
      </comparisons>
    </field>
  </fields>
</search>');
    }

    function it_exports_pattern_matchers(FieldSet $fieldSet, FieldConfigInterface $field)
    {
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->get('field1')->willReturn($field);

        $values = new ValuesBag();
        $values->addPatternMatch(new PatternMatch('foo', 'CONTAINS'));
        $values->addPatternMatch(new PatternMatch('bla', 'NOT_CONTAINS'));
        $values->addPatternMatch(new PatternMatch('bar', 'CONTAINS', true));
        $values->addPatternMatch(new PatternMatch('ooi', 'NOT_CONTAINS', true));
        $values->addPatternMatch(new PatternMatch('who', 'STARTS_WITH', true));
        $values->addPatternMatch(new PatternMatch('(\w+|\d+)', 'REGEX'));

        $group = new ValuesGroup();
        $group->addField('field1', $values);

        $condition = new SearchCondition($fieldSet->getWrappedObject(), $group);

        $this->exportCondition($condition)->shouldEqualXmlDocument('<?xml version="1.0" encoding="UTF-8"'.'?'.'>
<search xmlns="http://rollerworks.github.io/search/schema/dic/search"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="http://rollerworks.github.io/search/schema/dic/search http://rollerworks.github.io/search/schema/dic/search/input-1.0.xsd" logical="AND">
  <fields>
    <field name="field1">
      <pattern-matchers>
        <pattern-matcher type="CONTAINS" case-insensitive="false">foo</pattern-matcher>
        <pattern-matcher type="NOT_CONTAINS" case-insensitive="false">bla</pattern-matcher>
        <pattern-matcher type="CONTAINS" case-insensitive="true">bar</pattern-matcher>
        <pattern-matcher type="NOT_CONTAINS" case-insensitive="true">ooi</pattern-matcher>
        <pattern-matcher type="STARTS_WITH" case-insensitive="true">who</pattern-matcher>
        <pattern-matcher type="REGEX" case-insensitive="false">(\w+|\d+)</pattern-matcher>
      </pattern-matchers>
    </field>
  </fields>
</search>');
    }

    function it_supports_field_label(FieldSet $fieldSet, FieldConfigInterface $field, FieldLabelResolverInterface $labelResolver)
    {
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->get('field1')->willReturn($field);

        $labelResolver->resolveFieldLabel($fieldSet->getWrappedObject(), 'field1')->willReturn('user-id');
        $this->setLabelResolver($labelResolver);

        $values = new ValuesBag();
        $values->addSingleValue(new SingleValue('', 'value'));
        $values->addSingleValue(new SingleValue('', 'value2'));

        $group = new ValuesGroup();
        $group->addField('field1', $values);

        $condition = new SearchCondition($fieldSet->getWrappedObject(), $group);

        $this->exportCondition($condition, true)->shouldEqualXmlDocument('<?xml version="1.0" encoding="UTF-8"'.'?'.'>
<search xmlns="http://rollerworks.github.io/search/schema/dic/search"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="http://rollerworks.github.io/search/schema/dic/search http://rollerworks.github.io/search/schema/dic/search/input-1.0.xsd" logical="AND">
  <fields>
    <field name="user-id">
      <single-values>
        <value>value</value>
        <value>value2</value>
      </single-values>
    </field>
  </fields>
</search>');
    }

    function it_exports_groups(FieldSet $fieldSet, FieldConfigInterface $field)
    {
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->get('field1')->willReturn($field);

        $values = new ValuesBag();
        $values->addSingleValue(new SingleValue('', 'value'));
        $values->addSingleValue(new SingleValue('', 'value2'));

        $subGroup = new ValuesGroup();
        $subGroup->addField('field1', $values);

        $group = new ValuesGroup();
        $group->addGroup($subGroup);

        $condition = new SearchCondition($fieldSet->getWrappedObject(), $group);

        $this->exportCondition($condition)->shouldEqualXmlDocument('<?xml version="1.0" encoding="UTF-8"'.'?'.'>
<search xmlns="http://rollerworks.github.io/search/schema/dic/search"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="http://rollerworks.github.io/search/schema/dic/search http://rollerworks.github.io/search/schema/dic/search/input-1.0.xsd" logical="AND">
    <groups>
        <group logical="AND">
            <fields>
                <field name="field1">
                    <single-values>
                        <value>value</value>
                        <value>value2</value>
                    </single-values>
                </field>
            </fields>
        </group>
    </groups>
</search>');
    }

    function it_exports_subgroups(FieldSet $fieldSet, FieldConfigInterface $field)
    {
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->get('field1')->willReturn($field);

        $values = new ValuesBag();
        $values->addSingleValue(new SingleValue('', 'value'));
        $values->addSingleValue(new SingleValue('', 'value2'));

        $subGroup = new ValuesGroup();
        $subGroup->addField('field1', $values);

        $values = new ValuesBag();
        $values->addSingleValue(new SingleValue('', 'value'));
        $values->addSingleValue(new SingleValue('', 'value2'));

        $subGroup2 = new ValuesGroup();
        $subGroup2->addField('field1', $values);
        $subGroup->addGroup($subGroup2);

        $group = new ValuesGroup();
        $group->addGroup($subGroup);

        $condition = new SearchCondition($fieldSet->getWrappedObject(), $group);

        $this->exportCondition($condition)->shouldEqualXmlDocument('<?xml version="1.0" encoding="UTF-8"'.'?'.'>
<search xmlns="http://rollerworks.github.io/search/schema/dic/search"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="http://rollerworks.github.io/search/schema/dic/search http://rollerworks.github.io/search/schema/dic/search/input-1.0.xsd" logical="AND">
    <groups>
        <group logical="AND">
            <fields>
                <field name="field1">
                    <single-values>
                        <value>value</value>
                        <value>value2</value>
                    </single-values>
                </field>
            </fields>
            <groups>
                <group logical="AND">
                    <fields>
                        <field name="field1">
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
</search>');
    }

    function it_exports_logical_groups(FieldSet $fieldSet, FieldConfigInterface $field)
    {
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->get('field1')->willReturn($field);

        $values = new ValuesBag();
        $values->addSingleValue(new SingleValue('', 'value'));
        $values->addSingleValue(new SingleValue('', 'value2'));

        $group = new ValuesGroup(ValuesGroup::GROUP_LOGICAL_OR);
        $group->addField('field1', $values);

        $condition = new SearchCondition($fieldSet->getWrappedObject(), $group);

        $this->exportCondition($condition)->shouldEqualXmlDocument('<?xml version="1.0" encoding="UTF-8"'.'?'.'>
<search xmlns="http://rollerworks.github.io/search/schema/dic/search"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="http://rollerworks.github.io/search/schema/dic/search http://rollerworks.github.io/search/schema/dic/search/input-1.0.xsd" logical="OR">
  <fields>
    <field name="field1">
      <single-values>
        <value>value</value>
        <value>value2</value>
      </single-values>
    </field>
  </fields>
</search>');
    }

    function it_exports_logical_subgroups(FieldSet $fieldSet, FieldConfigInterface $field)
    {
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->get('field1')->willReturn($field);

        $values = new ValuesBag();
        $values->addSingleValue(new SingleValue('', 'value'));
        $values->addSingleValue(new SingleValue('', 'value2'));

        $subGroup = new ValuesGroup(ValuesGroup::GROUP_LOGICAL_OR);
        $subGroup->addField('field1', $values);

        $group = new ValuesGroup();
        $group->addGroup($subGroup);

        $condition = new SearchCondition($fieldSet->getWrappedObject(), $group);

        $this->exportCondition($condition)->shouldEqualXmlDocument('<?xml version="1.0" encoding="UTF-8"'.'?'.'>
<search xmlns="http://rollerworks.github.io/search/schema/dic/search"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="http://rollerworks.github.io/search/schema/dic/search http://rollerworks.github.io/search/schema/dic/search/input-1.0.xsd" logical="AND">
    <groups>
        <group logical="OR">
            <fields>
                <field name="field1">
                    <single-values>
                        <value>value</value>
                        <value>value2</value>
                    </single-values>
                </field>
            </fields>
        </group>
    </groups>
</search>');
    }

    public function getMatchers()
    {
        return array(
            'equalXmlDocument' => function($subject, $expectedInput) {
                $expected = new \DOMDocument;
                $expected->preserveWhiteSpace = false;
                $expected->loadXML($expectedInput);

                $actual = new \DOMDocument;
                $actual->preserveWhiteSpace = false;
                $actual->loadXML($subject);

                $expected = $expected->C14N();
                $actual = $actual->C14N();

                return $expected === $actual;
            },
        );
    }
}
