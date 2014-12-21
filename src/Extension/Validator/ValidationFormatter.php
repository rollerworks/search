<?php

/*
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Extension\Validator;

use Rollerworks\Component\Search\FieldConfigInterface;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\FormatterInterface;
use Rollerworks\Component\Search\SearchConditionInterface;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\ValuesBag;
use Rollerworks\Component\Search\ValuesError;
use Rollerworks\Component\Search\ValuesGroup;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ValidatorInterface as LegacyValidator;

/**
 * Validates the values using the configured constraints
 * of the corresponding field.
 *
 * Any violation is then mapped on the ValuesBag and ValuesGroup.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class ValidationFormatter implements FormatterInterface
{
    /**
     * @var LegacyValidator|ValidatorInterface
     */
    private $validator;

    /**
     * @param LegacyValidator|ValidatorInterface $validator
     */
    public function __construct($validator)
    {
        $this->validator = $validator;
    }

    /**
     * {@inheritdoc}
     */
    public function format(SearchConditionInterface $condition)
    {
        if (true === $condition->getValuesGroup()->hasErrors()) {
            return;
        }

        $group = $condition->getValuesGroup();
        $this->validateValuesGroup($group, $condition->getFieldSet());
    }

    /**
     * @param ValuesGroup $valuesGroup
     * @param FieldSet    $fieldSet
     */
    private function validateValuesGroup(ValuesGroup $valuesGroup, FieldSet $fieldSet)
    {
        foreach ($valuesGroup->getGroups() as $i => $group) {
            $this->validateValuesGroup($group, $fieldSet);
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

            $groups = self::getValidationGroups($config);
            $constraints = $config->getOption('constraints');

            foreach ($constraints as $constraint) {
                foreach ($groups as $group) {
                    if (in_array($group, $constraint->groups, true)) {
                        $this->validateValuesBag(
                            $valuesGroup,
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
     * @param ValuesGroup          $valuesGroup
     * @param ValuesBag            $valuesBag
     * @param FieldConfigInterface $field
     * @param Constraint[]         $constraints
     */
    private function validateValuesBag(
        ValuesGroup $valuesGroup,
        ValuesBag $valuesBag,
        FieldConfigInterface $field,
        array $constraints
    ) {
        $options = $field->getOptions();

        foreach ($valuesBag->getSingleValues() as $i => $value) {
            $this->validateValue(
                $value->getValue(),
                $constraints,
                'singleValues['.$i.'].value',
                $valuesGroup,
                $valuesBag
            );
        }

        foreach ($valuesBag->getExcludedValues() as $i => $value) {
            $this->validateValue(
                $value->getValue(),
                $constraints,
                'excludedValues['.$i.'].value',
                $valuesGroup,
                $valuesBag
            );
        }

        foreach ($valuesBag->getRanges() as $i => $value) {
            $this->validateRange(
                'ranges['.$i.']',
                $valuesGroup,
                $valuesBag,
                $value,
                $field,
                $constraints,
                $options
            );
        }

        foreach ($valuesBag->getExcludedRanges() as $i => $value) {
            $this->validateRange(
                'excludedRanges['.$i.']',
                $valuesGroup,
                $valuesBag,
                $value,
                $field,
                $constraints,
                $options
            );
        }

        foreach ($valuesBag->getComparisons() as $i => $value) {
            $this->validateValue(
                $value->getValue(),
                $constraints,
                'comparisons['.$i.'].value',
                $valuesGroup,
                $valuesBag
            );
        }

        foreach ($valuesBag->getPatternMatchers() as $i => $value) {
            $this->validateValue(
                $value->getValue(),
                $constraints,
                'patternMatchers['.$i.'].value',
                $valuesGroup,
                $valuesBag
            );
        }
    }

    /**
     * @param string               $subPath
     * @param ValuesGroup          $valuesGroup
     * @param ValuesBag            $valuesBag
     * @param Range                $range
     * @param FieldConfigInterface $field
     * @param Constraint[]         $constraints
     * @param array                $options
     */
    private function validateRange(
        $subPath,
        ValuesGroup $valuesGroup,
        ValuesBag $valuesBag,
        Range $range,
        FieldConfigInterface $field,
        array $constraints,
        array $options
    ) {
        $this->validateValue($range->getLower(), $constraints, $subPath.'.lower', $valuesGroup, $valuesBag);
        $this->validateValue($range->getUpper(), $constraints, $subPath.'.upper', $valuesGroup, $valuesBag);

        // Only validate when the range is inclusive, its not really possible to validate an exclusive range.
        // ]1-5[ should be validated as ">0 AND 6<", but as the value-structure is not not known at this point
        // it's not possible.

        if ($range->isLowerInclusive() && $range->isUpperInclusive()) {
            if (!$field->getValueComparison()->isLower($range->getLower(), $range->getUpper(), $options)) {
                $valuesGroup->setHasErrors(true);
                $valuesBag->addError(
                    new ValuesError(
                        $subPath,
                        strtr(
                            'Lower range-value {{ lower }} should be lower then upper range-value {{ upper }}.',
                            array(
                                '{{ lower }}' => $range->getViewLower(),
                                '{{ upper }}' => $range->getViewUpper(),
                            )
                        ),
                        'Lower range-value {{ lower }} should be lower then upper range-value {{ upper }}.',
                        array(
                            '{{ lower }}' => $range->getViewLower(),
                            '{{ upper }}' => $range->getViewUpper(),
                        )
                    )
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
        $groups = $field->getOption('validation_groups', array(Constraint::DEFAULT_GROUP));

        if (!is_string($groups) && is_callable($groups)) {
            $groups = call_user_func($groups, $field);
        }

        return (array) $groups;
    }

    /**
     * @param mixed       $value
     * @param array       $constraints
     * @param string      $subPath
     * @param ValuesGroup $valuesGroup
     * @param ValuesBag   $valuesBag
     * @param string[]    $groups
     */
    private function validateValue(
        $value,
        array $constraints,
        $subPath,
        ValuesGroup $valuesGroup,
        ValuesBag $valuesBag,
        $groups = null
    ) {
        if ($this->validator instanceof LegacyValidator) {
            $violations = $this->validator->validateValue($value, $constraints, $groups);
        } else {
            $violations = $this->validator->validate($value, $constraints, $groups);
        }

        if (count($violations) > 0) {
            $valuesGroup->setHasErrors(true);

            foreach ($violations as $violation) {
                $valuesBag->addError(
                    new ValuesError(
                        $subPath,
                        $violation->getMessage(),
                        $violation->getMessageTemplate(),
                        $violation->getMessageParameters(),
                        $violation->getMessagePluralization()
                    )
                );
            }
        }
    }
}
