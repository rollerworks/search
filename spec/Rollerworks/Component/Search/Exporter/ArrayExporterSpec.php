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

class ArrayExporterSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Rollerworks\Component\Search\Exporter\ArrayExporter');
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

        $this->exportCondition($condition)->shouldBeLike(
            array(
                'fields' => array(
                    'field1' => array(
                        'single-values' => array('value', 'value2')
                    )
                )
            )
        );
    }

    function it_exports_with_multiple_fields(FieldSet $fieldSet, FieldConfigInterface $field, FieldConfigInterface $field2)
    {
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->get('field1')->willReturn($field);

        $fieldSet->has('field2')->willReturn(true);
        $fieldSet->get('field2')->willReturn($field);

        $values = new ValuesBag();
        $values->addSingleValue(new SingleValue('', 'value'));
        $values->addSingleValue(new SingleValue('', 'value2'));

        $group = new ValuesGroup();
        $group->addField('field1', $values);

        $values = new ValuesBag();
        $values->addSingleValue(new SingleValue('', '1value'));
        $values->addSingleValue(new SingleValue('', '1value2'));
        $group->addField('field2', $values);

        $condition = new SearchCondition($fieldSet->getWrappedObject(), $group);

        $this->exportCondition($condition)->shouldBeLike(
            array(
                'fields' => array(
                    'field1' => array(
                        'single-values' => array('value', 'value2')
                    ),
                    'field2' => array(
                        'single-values' => array('1value', '1value2')
                    )
                )
            )
        );
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

        $this->exportCondition($condition)->shouldBeLike(
            array(
                'fields' => array(
                    'field1' => array(
                        'excluded-values' => array('value', 'value2')
                    )
                )
            )
        );
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

        $this->exportCondition($condition)->shouldBeLike(
            array(
                'fields' => array(
                    'field1' => array(
                        'ranges' => array(
                            array('lower' => '10', 'upper' => '20'),
                            array('lower' => '30', 'upper' => '50'),

                            array('lower' => '30', 'upper' => '50', 'inclusive-upper' => false),
                            array('lower' => '30', 'upper' => '50', 'inclusive-lower' => false),
                        )
                    )
                )
            )
        );
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

        $this->exportCondition($condition)->shouldBeLike(
            array(
                'fields' => array(
                    'field1' => array(
                        'excluded-ranges' => array(
                            array('lower' => '10', 'upper' => '20'),
                            array('lower' => '30', 'upper' => '50'),
                        )
                    )
                )
            )
        );
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

        $this->exportCondition($condition)->shouldBeLike(
            array(
                'fields' => array(
                    'field1' => array(
                        'comparisons' => array(
                            array('operator' => '>', 'value' => '10'),
                            array('operator' => '<', 'value' => '50'),
                        )
                    )
                )
            )
        );
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

        $this->exportCondition($condition)->shouldBeLike(
            array(
                'fields' => array(
                    'field1' => array(
                        'pattern-matchers' => array(
                            array('value' => 'foo', 'type' => 'CONTAINS', 'case-insensitive' => false),
                            array('value' => 'bla', 'type' => 'NOT_CONTAINS', 'case-insensitive' => false),
                            array('value' => 'bar', 'type' => 'CONTAINS', 'case-insensitive' => true),
                            array('value' => 'ooi', 'type' => 'NOT_CONTAINS', 'case-insensitive' => true),
                            array('value' => 'who', 'type' => 'STARTS_WITH', 'case-insensitive' => true),
                            array('value' => '(\w+|\d+)', 'type' => 'REGEX', 'case-insensitive' => false),
                        )
                    )
                )
            )
        );
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

        $this->exportCondition($condition, true)->shouldBeLike(
            array(
                'fields' => array(
                    'user-id' => array(
                        'single-values' => array('value', 'value2')
                    )
                )
            )
        );
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

        $this->exportCondition($condition)->shouldBeLike(
            array(
                'groups' => array(
                    array(
                        'fields' => array(
                            'field1' => array(
                                'single-values' => array('value', 'value2')
                            )
                        )
                    )
                )
            )
        );
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

        $this->exportCondition($condition)->shouldBeLike(
            array(
                'groups' => array(
                    array(
                        'fields' => array(
                            'field1' => array(
                                'single-values' => array('value', 'value2')
                            )
                        ),
                        'groups' => array(
                            array(
                                'fields' => array(
                                    'field1' => array(
                                        'single-values' => array('value', 'value2')
                                    )
                                )
                            )
                        )
                    )
                )
            )
        );
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

        $this->exportCondition($condition)->shouldBeLike(
            array(
                'logical-case' => 'OR',
                'fields' => array(
                    'field1' => array(
                        'single-values' => array('value', 'value2')
                    )
                )
            )
        );
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

        $this->exportCondition($condition)->shouldBeLike(
            array(
                'groups' => array(
                    array(
                        'logical-case' => 'OR',
                        'fields' => array(
                            'field1' => array(
                                'single-values' => array('value', 'value2')
                            )
                        )
                    )
                )
            )
        );
    }
}
