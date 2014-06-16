<?php

/**
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Extension\Validator\Constraints;

use Rollerworks\Component\Search\FieldConfigInterface;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\SearchConditionInterface;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\ValuesBag as SearchValuesBag;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * ValuesGroup validator validates a `SearchValuesBag` object.
 *
 * All the value-types and subgroups are validated.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class ValuesGroupValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($condition, Constraint $constraint)
    {
        if (!$condition instanceof SearchConditionInterface) {
            return;
        }

        $fieldSet = $condition->getFieldSet();
        $valuesGroup = $condition->getValuesGroup();

        foreach ($valuesGroup->getGroups() as $i => $group) {
            $this->context->validateValue(
                new SearchCondition($fieldSet, $group),
                $constraint,
                'groups['.$i.']'
            );
        }

        foreach ($valuesGroup->getFields() as $fieldName => $values) {
            if (!$fieldSet->has($fieldName)) {
                $valuesGroup->removeField($fieldName);

                continue;
            }

            $config = $fieldSet->get($fieldName);

            // We currently don't validate values without constraints,
            // For ranges this means that the lower-upper bounds are not properly validated
            if (!$config->hasOption('constraints')) {
                continue;
            }

            // Validate the values data only if transformation succeeded
            $groups = self::getValidationGroups($config);

            // Validate the data against the constraints defined
            // in the field
            $constraints = $config->getOption('constraints');

            foreach ($constraints as $constraint) {
                foreach ($groups as $group) {
                    if (in_array($group, $constraint->groups)) {
                        $this->validateValuesBag(
                            sprintf('fields[%s]', $fieldName),
                            $values,
                            $fieldSet->get($fieldName),
                            $constraints
                        );

                        // Prevent duplicate validation
                        continue 2;
                    }
                }
            }
        }
    }

    /**
     * @param string               $subPath
     * @param SearchValuesBag      $valuesBag
     * @param FieldConfigInterface $field
     * @param Constraint[]         $constraints
     */
    private function validateValuesBag($subPath, SearchValuesBag $valuesBag, FieldConfigInterface $field, $constraints)
    {
        $options = $field->getOptions();

        if ($valuesBag->hasSingleValues()) {
            foreach ($valuesBag->getSingleValues() as $i => $value) {
                $this->context->validateValue($value->getValue(), $constraints, $subPath.'.singleValues['.$i.'].value');
            }
        }

        if ($valuesBag->hasExcludedValues()) {
            foreach ($valuesBag->getExcludedValues() as $i => $value) {
                $this->context->validateValue(
                    $value->getValue(),
                    $constraints,
                    $subPath.'.excludedValues['.$i.'].value'
                );
            }
        }

        if ($valuesBag->hasRanges()) {
            foreach ($valuesBag->getRanges() as $i => $value) {
                $this->validateRange($subPath.'.ranges['.$i.']', $value, $field, $constraints, $options);
            }
        }

        if ($valuesBag->hasExcludedRanges()) {
            foreach ($valuesBag->getExcludedRanges() as $i => $value) {
                $this->validateRange($subPath.'.excludedRanges['.$i.']', $value, $field, $constraints, $options);
            }
        }

        if ($valuesBag->hasComparisons()) {
            foreach ($valuesBag->getComparisons() as $i => $value) {
                $this->context->validateValue($value->getValue(), $constraints, $subPath.'.comparisons['.$i.'].value');
            }
        }

        if ($valuesBag->hasPatternMatchers()) {
            foreach ($valuesBag->getPatternMatchers() as $i => $value) {
                $this->context->validateValue(
                    $value->getValue(),
                    $constraints,
                    $subPath.'.patternMatchers['.$i.'].value'
                );
            }
        }
    }

    /**
     * @param string               $subPath
     * @param Range                $range
     * @param FieldConfigInterface $field
     * @param Constraint[]         $constraints
     * @param array                $options
     */
    private function validateRange($subPath, Range $range, FieldConfigInterface $field, $constraints, array $options)
    {
        $this->context->validateValue($range->getLower(), $constraints, $subPath.'.lower');
        $this->context->validateValue($range->getUpper(), $constraints, $subPath.'.upper');

        // Only validate when the range is inclusive, its not really possible to validate an exclusive range
        // ]1-5[ should be validated as ">0 AND 6<", but as the value-structure is not not known at this point
        // it's not possible.

        if ($range->isLowerInclusive() && $range->isUpperInclusive()) {
            if (!$field->getValueComparison()->isLower($range->getLower(), $range->getUpper(), $options)) {
                $this->context->addViolationAt(
                    $subPath,
                    'Lower range-value {{ lower }} should be lower then upper range-value {{ upper }}.',
                    array(
                        '{{ lower }}' => $range->getViewLower(),
                        '{{ upper }}' => $range->getViewUpper()
                    ),
                    $range
                );
            }
        }
    }

    /**
     * Returns the validation groups of the given field.
     *
     * @param FieldConfigInterface $field The field
     *
     * @return array The validation groups.
     */
    private static function getValidationGroups(FieldConfigInterface $field)
    {
        $groups = $field->getOption('validation_groups');

        if (null !== $groups) {
            return self::resolveValidationGroups($groups, $field);
        }

        return array(Constraint::DEFAULT_GROUP);
    }

    /**
     * Post-processes the validation groups option for a given field.
     *
     * @param array|callable       $groups The validation groups
     * @param FieldConfigInterface $field  The field form
     *
     * @return array The validation groups.
     */
    private static function resolveValidationGroups($groups, FieldConfigInterface $field)
    {
        if (!is_string($groups) && is_callable($groups)) {
            $groups = call_user_func($groups, $field);
        }

        return (array) $groups;
    }
}
