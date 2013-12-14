<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace spec\Rollerworks\Component\Search\Input;

use PhpSpec\ObjectBehavior;
use Rollerworks\Component\Search\Exception\FieldRequiredException;
use Rollerworks\Component\Search\Exception\GroupsNestingException;
use Rollerworks\Component\Search\Exception\GroupsOverflowException;
use Rollerworks\Component\Search\Exception\UnknownFieldException;
use Rollerworks\Component\Search\Exception\UnsupportedValueTypeException;
use Rollerworks\Component\Search\Exception\ValuesOverflowException;
use Rollerworks\Component\Search\FieldAliasResolverInterface;
use Rollerworks\Component\Search\FieldConfigInterface;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\SingleValue;
use Rollerworks\Component\Search\ValuesBag;
use Rollerworks\Component\Search\ValuesGroup;

class ArrayInputSpec extends ObjectBehavior
{
    function it_is_initializable()
    {
        $this->shouldHaveType('Rollerworks\Component\Search\Input\ArrayInput');
        $this->shouldImplement('Rollerworks\Component\Search\InputProcessorInterface');
    }

    function it_processes_single_values(FieldSet $fieldSet, FieldConfigInterface $field)
    {
        $field->isRequired()->willReturn(false);
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->get('field1')->willReturn($field);
        $fieldSet->all()->willReturn(array('field1' => $field));

        $values = new ValuesBag();
        $values->addSingleValue(new SingleValue('value'));
        $values->addSingleValue(new SingleValue('value2'));

        $expectedGroup = new ValuesGroup();
        $expectedGroup->addField('field1', $values);

        $condition = new SearchCondition($fieldSet->getWrappedObject(), $expectedGroup);

        $this->setFieldSet($fieldSet);
        $this->process(
            array(
                'fields' => array(
                    'field1' => array(
                        'single-values' => array('value', 'value2')
                    )
                )
            )
        )->shouldBeLike($condition);
    }

    function it_merges_field_alias(FieldSet $fieldSet, FieldConfigInterface $field, FieldAliasResolverInterface $aliasResolver)
    {
        $aliasResolver->resolveFieldName($fieldSet->getWrappedObject(), 'field1')->willReturn('field1');
        $aliasResolver->resolveFieldName($fieldSet->getWrappedObject(), 'field2')->willReturn('field1');
        $this->setAliasResolver($aliasResolver);

        $field->isRequired()->willReturn(false);
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->get('field1')->willReturn($field);
        $fieldSet->all()->willReturn(array('field1' => $field));

        $values = new ValuesBag();
        $values->addSingleValue(new SingleValue('value'));
        $values->addSingleValue(new SingleValue('value2'));
        $values->addSingleValue(new SingleValue('value3'));
        $values->addSingleValue(new SingleValue('value4'));

        $expectedGroup = new ValuesGroup();
        $expectedGroup->addField('field1', $values);

        $condition = new SearchCondition($fieldSet->getWrappedObject(), $expectedGroup);

        $this->setFieldSet($fieldSet);
        $this->process(
            array(
                'fields' => array(
                    'field1' => array('single-values' => array('value', 'value2')),
                    'field2' => array('single-values' => array('value3', 'value4'))
                )
            )
        )->shouldBeLike($condition);
    }

    function it_processes_excluded_values(FieldSet $fieldSet, FieldConfigInterface $field)
    {
        $field->isRequired()->willReturn(false);
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->get('field1')->willReturn($field);
        $fieldSet->all()->willReturn(array('field1' => $field));

        $values = new ValuesBag();
        $values->addExcludedValue(new SingleValue('value'));
        $values->addExcludedValue(new SingleValue('value2'));

        $expectedGroup = new ValuesGroup();
        $expectedGroup->addField('field1', $values);

        $condition = new SearchCondition($fieldSet->getWrappedObject(), $expectedGroup);

        $this->setFieldSet($fieldSet);
        $this->process(
            array(
                'fields' => array(
                    'field1' => array(
                        'excluded-values' => array('value', 'value2')
                    )
                )
            )
        )->shouldBeLike($condition);
    }

    function it_processes_ranges(FieldSet $fieldSet, FieldConfigInterface $field)
    {
        $field->isRequired()->willReturn(false);
        $field->acceptRanges()->willReturn(true);

        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->get('field1')->willReturn($field);
        $fieldSet->all()->willReturn(array('field1' => $field));

        $values = new ValuesBag();
        $values->addRange(new Range(10, 20));
        $values->addRange(new Range(30, 40));

        $values->addRange(new Range(50, 60, false));
        $values->addRange(new Range(70, 80, true, false));

        $expectedGroup = new ValuesGroup();
        $expectedGroup->addField('field1', $values);

        $condition = new SearchCondition($fieldSet->getWrappedObject(), $expectedGroup);

        $this->setFieldSet($fieldSet);
        $this->process(
            array(
                'fields' => array(
                    'field1' => array(
                        'ranges' => array(
                            array('lower' => 10, 'upper' => 20),
                            array('lower' => 30, 'upper' => 40),

                            array('lower' => 50, 'upper' => 60, 'inclusive-lower' => false),
                            array('lower' => 70, 'upper' => 80, 'inclusive-upper' => false),
                        )
                    )
                )
            )
        )->shouldBeLike($condition);
    }

    function it_processes_excluded_ranges(FieldSet $fieldSet, FieldConfigInterface $field)
    {
        $field->isRequired()->willReturn(false);
        $field->acceptRanges()->willReturn(true);

        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->get('field1')->willReturn($field);
        $fieldSet->all()->willReturn(array('field1' => $field));

        $values = new ValuesBag();
        $values->addExcludedRange(new Range(10, 20));
        $values->addExcludedRange(new Range(30, 40));

        $expectedGroup = new ValuesGroup();
        $expectedGroup->addField('field1', $values);

        $condition = new SearchCondition($fieldSet->getWrappedObject(), $expectedGroup);

        $this->setFieldSet($fieldSet);
        $this->process(
            array(
                'fields' => array(
                    'field1' => array(
                        'excluded-ranges' => array(array('lower' => 10, 'upper' => 20), array('lower' => 30, 'upper' => 40))
                    )
                )
            )
        )->shouldBeLike($condition);
    }

    function it_processes_comparisons(FieldSet $fieldSet, FieldConfigInterface $field)
    {
        $field->isRequired()->willReturn(false);
        $field->acceptCompares()->willReturn(true);

        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->get('field1')->willReturn($field);
        $fieldSet->all()->willReturn(array('field1' => $field));

        $values = new ValuesBag();
        $values->addComparison(new Compare(10, '>'));
        $values->addComparison(new Compare(50, '<'));

        $expectedGroup = new ValuesGroup();
        $expectedGroup->addField('field1', $values);

        $condition = new SearchCondition($fieldSet->getWrappedObject(), $expectedGroup);

        $this->setFieldSet($fieldSet);
        $this->process(
            array(
                'fields' => array(
                    'field1' => array(
                        'comparisons' => array(array('value' => 10, 'operator' => '>'), array('value' => 50, 'operator' => '<'))
                    )
                )
            )
        )->shouldBeLike($condition);
    }

    function it_processes_pattern_matchers(FieldSet $fieldSet, FieldConfigInterface $field)
    {
        $field->isRequired()->willReturn(false);
        $field->acceptCompares()->willReturn(true);

        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->get('field1')->willReturn($field);
        $fieldSet->all()->willReturn(array('field1' => $field));

        $values = new ValuesBag();
        $values->addPatternMatch(new PatternMatch('foo', 'CONTAINS'));
        $values->addPatternMatch(new PatternMatch('bar', 'ENDS_WITH', true));

        $expectedGroup = new ValuesGroup();
        $expectedGroup->addField('field1', $values);

        $condition = new SearchCondition($fieldSet->getWrappedObject(), $expectedGroup);

        $this->setFieldSet($fieldSet);
        $this->process(
            array(
                'fields' => array(
                    'field1' => array(
                        'pattern-matchers' => array(
                            array('value' => 'foo', 'type' => 'CONTAINS'),
                            array('value' => 'bar', 'type' => 'ENDS_WITH', 'case-insensitive' => true)
                        )
                    )
                )
            )
        )->shouldBeLike($condition);
    }

    function it_processes_groups(FieldSet $fieldSet, FieldConfigInterface $field)
    {
        $field->isRequired()->willReturn(false);
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->get('field1')->willReturn($field);
        $fieldSet->all()->willReturn(array('field1' => $field));

        $values = new ValuesBag();
        $values->addSingleValue(new SingleValue('value'));
        $values->addSingleValue(new SingleValue('value2'));

        $expectedGroup = new ValuesGroup();

        $subGroup = new ValuesGroup();
        $subGroup->addField('field1', $values);
        $expectedGroup->addGroup($subGroup);

        $condition = new SearchCondition($fieldSet->getWrappedObject(), $expectedGroup);

        $this->setFieldSet($fieldSet);
        $this->process(
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
        )->shouldBeLike($condition);
    }

    function it_processes_multiple_groups(FieldSet $fieldSet, FieldConfigInterface $field)
    {
        $field->isRequired()->willReturn(false);
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->get('field1')->willReturn($field);
        $fieldSet->all()->willReturn(array('field1' => $field));

        $values = new ValuesBag();
        $values->addSingleValue(new SingleValue('value'));
        $values->addSingleValue(new SingleValue('value2'));

        $expectedGroup = new ValuesGroup();

        $subGroup = new ValuesGroup();
        $subGroup->addField('field1', $values);
        $expectedGroup->addGroup($subGroup);

        $values = new ValuesBag();
        $values->addSingleValue(new SingleValue('value3'));
        $values->addSingleValue(new SingleValue('value4'));

        $subGroup = new ValuesGroup();
        $subGroup->addField('field1', $values);
        $expectedGroup->addGroup($subGroup);

        $condition = new SearchCondition($fieldSet->getWrappedObject(), $expectedGroup);

        $this->setFieldSet($fieldSet);
        $this->process(
            array(
                'groups' => array(
                    array(
                        'fields' => array(
                            'field1' => array(
                                'single-values' => array('value', 'value2')
                            )
                        )
                    ),
                    array(
                        'fields' => array(
                            'field1' => array(
                                'single-values' => array('value3', 'value4')
                            )
                        )
                    )
                )
            )
        )->shouldBeLike($condition);
    }

    function it_processes_logical_groups(FieldSet $fieldSet, FieldConfigInterface $field)
    {
        $field->isRequired()->willReturn(false);
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->get('field1')->willReturn($field);
        $fieldSet->all()->willReturn(array('field1' => $field));

        $values = new ValuesBag();
        $values->addSingleValue(new SingleValue('value'));
        $values->addSingleValue(new SingleValue('value2'));

        $expectedGroup = new ValuesGroup();

        $subGroup = new ValuesGroup(ValuesGroup::GROUP_LOGICAL_OR);
        $subGroup->addField('field1', $values);
        $expectedGroup->addGroup($subGroup);

        $condition = new SearchCondition($fieldSet->getWrappedObject(), $expectedGroup);

        $this->setFieldSet($fieldSet);
        $this->process(
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
        )->shouldBeLike($condition);
    }

    function it_errors_when_maximum_values_count_is_exceeded(FieldSet $fieldSet, FieldConfigInterface $field)
    {
        $field->isRequired()->willReturn(false);
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->get('field1')->willReturn($field);
        $fieldSet->all()->willReturn(array('field1' => $field));

        $this->setFieldSet($fieldSet);
        $this->setMaxValues(3);

        $this->shouldThrow(new ValuesOverflowException('field1', 3, 5, 0, 0))->during('process', array(
            array(
                'fields' => array(
                    'field1' => array(
                        'single-values' => array('value', 'value2', 'value3', 'value4', 'value5')
                    )
                )
            )
        ));

        $this->shouldThrow(new ValuesOverflowException('field1', 3, 5, 1, 0))->during('process', array(
            array(
                'groups' => array(1 =>
                    array(
                        'fields' => array(
                            'field1' => array(
                                'single-values' => array('value', 'value2', 'value3', 'value4', 'value5')
                            )
                        )
                    )
                )
            )
        ));
    }

    function it_errors_when_maximum_groups_count_is_exceeded(FieldSet $fieldSet, FieldConfigInterface $field)
    {
        $field->isRequired()->willReturn(false);
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->get('field1')->willReturn($field);
        $fieldSet->all()->willReturn(array('field1' => $field));

        $this->setFieldSet($fieldSet);
        $this->setMaxGroups(3);

        $this->shouldThrow(new GroupsOverflowException(3, 4, 3, 0))->during('process', array(
            array(
                'groups' => array(
                    array(
                        'fields' => array(
                            'field1' => array(
                                'single-values' => array('value', 'value2', 'value3', 'value4', 'value5')
                            )
                        )
                    ),
                    array(
                        'fields' => array(
                            'field1' => array(
                                'single-values' => array('value', 'value2', 'value3', 'value4', 'value5')
                            )
                        )
                    ),
                    array(
                        'fields' => array(
                            'field1' => array(
                                'single-values' => array('value', 'value2', 'value3', 'value4', 'value5')
                            )
                        )
                    ),
                    array(
                        'fields' => array(
                            'field1' => array(
                                'single-values' => array('value', 'value2', 'value3', 'value4', 'value5')
                            )
                        )
                    ),
                )
            )
        ));
    }

    function it_errors_when_maximum_nesting_level_is_reached(FieldSet $fieldSet, FieldConfigInterface $field)
    {
        $field->isRequired()->willReturn(false);
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->get('field1')->willReturn($field);
        $fieldSet->all()->willReturn(array('field1' => $field));

        $this->setFieldSet($fieldSet);
        $this->setMaxNestingLevel(1);

        $this->shouldThrow(new GroupsNestingException(1, 0, 2))->during('process', array(
            array(
                'groups' => array(
                    array(
                        'groups' => array(
                            array(
                                'groups' => array(
                                    array(
                                        'fields' => array(
                                            'field1' => array(
                                                'single-values' => array('value', 'value2', 'value3', 'value4', 'value5')
                                            )
                                        )
                                    ),
                                    array(
                                        'fields' => array(
                                            'field1' => array(
                                                'single-values' => array('value', 'value2', 'value3', 'value4', 'value5')
                                            )
                                        )
                                    ),
                                    array(
                                        'fields' => array(
                                            'field1' => array(
                                                'single-values' => array('value', 'value2', 'value3', 'value4', 'value5')
                                            )
                                        )
                                    ),
                                    array(
                                        'fields' => array(
                                            'field1' => array(
                                                'single-values' => array('value', 'value2', 'value3', 'value4', 'value5')
                                            )
                                        )
                                    ),
                                )
                            )
                        )
                    )
                )
            )
        ));
    }

    function it_errors_when_the_field_does_not_exist_in_fieldset(FieldSet $fieldSet, FieldConfigInterface $field)
    {
        $field->isRequired()->willReturn(false);
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->get('field1')->willReturn($field);
        $fieldSet->all()->willReturn(array('field1' => $field));
        $fieldSet->has('field2')->willReturn(false);

        $this->setFieldSet($fieldSet);

        $this->shouldThrow(new UnknownFieldException('field2'))->during('process', array(
            array(
                'fields' => array(
                    'field2' => array(
                        'single-values' => array('value', 'value2', 'value3', 'value4', 'value5')
                    )
                )
            )
        ));
    }

    function it_errors_when_the_field_does_not_support_the_value_type(FieldSet $fieldSet, FieldConfigInterface $field, FieldConfigInterface $field2)
    {
        $field->isRequired()->willReturn(false);
        $field->acceptRanges()->willReturn(false);
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->get('field1')->willReturn($field);

        $field2->isRequired()->willReturn(false);
        $field2->acceptCompares()->willReturn(false);
        $fieldSet->has('field2')->willReturn(true);
        $fieldSet->get('field2')->willReturn($field2);

        $fieldSet->all()->willReturn(array('field1' => $field, 'field2' => $field2));

        $this->setFieldSet($fieldSet);

        $this->shouldThrow(new UnsupportedValueTypeException('field1', 'range'))->during('process', array(
            array(
                'fields' => array(
                    'field1' => array(
                        'ranges' => array(array('lower' => 10, 'upper' => 20))
                    )
                )
            )
        ));

        $this->shouldThrow(new UnsupportedValueTypeException('field2', 'comparison'))->during('process', array(
            array(
                'fields' => array(
                    'field2' => array(
                        'comparisons' => array(array('value' => 10, 'operator' => '>'))
                    )
                )
            )
        ));
    }

    function it_errors_when_a_field_is_required_but_not_set(FieldSet $fieldSet, FieldConfigInterface $field, FieldConfigInterface $field2)
    {
        $field->isRequired()->willReturn(false);
        $field->acceptRanges()->willReturn(false);
        $fieldSet->has('field1')->willReturn(true);
        $fieldSet->get('field1')->willReturn($field);

        $field2->isRequired()->willReturn(true);
        $field2->acceptCompares()->willReturn(false);
        $fieldSet->has('field2')->willReturn(true);
        $fieldSet->get('field2')->willReturn($field2);

        $fieldSet->all()->willReturn(array('field1' => $field, 'field2' => $field2));

        $this->setFieldSet($fieldSet);

        $this->shouldThrow(new FieldRequiredException('field2', 0, 0))->during('process', array(
            array(
                'fields' => array(
                    'field1' => array(
                        'single-values' => array('value', 'value2')
                    )
                )
            )
        ));

        $this->shouldThrow(new FieldRequiredException('field2', 0, 0))->during('process', array(
            array(
                'fields' => array(
                    'field1' => array()
                )
            )
        ));
    }
}
