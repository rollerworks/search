<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Extension\Symfony\Validator;

use Rollerworks\Component\Search\FieldConfigInterface;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\SearchConditionInterface;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\ValuesBag;
use Rollerworks\Component\Search\ValuesError;
use Rollerworks\Component\Search\ValuesGroup;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\ValidatorInterface as LegacyValidator;

/**
 * Validates the values using the configured constraints
 * of the corresponding field.
 *
 * Violation are then mapped on the related ValuesBag.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class Validator
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
     * Validate the SearchCondition using the configured validation
     * constraints.
     *
     * If the condition has invalid values the constraintViolations
     * are converted to `Rollerworks\Component\Search\ValuesError` objects
     * and added to the related ValuesBag object.
     *
     * @param SearchConditionInterface $condition
     *
     * @return bool Returns true when the condition is valid
     *              false otherwise
     */
    public function validate(SearchConditionInterface $condition)
    {
        if ($condition->getValuesGroup()->hasErrors(true)) {
            return;
        }

        if ($condition->getValuesGroup()->isDataLocked()) {
            throw new \RuntimeException('Unable to validate locked ValuesGroup.');
        }

        $group = $condition->getValuesGroup();
        $this->validateValuesGroup($group, $condition->getFieldSet());

        return !$group->hasErrors(true);
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
            $constraints = $config->getOption('constraints', []);

            // Don't validate values without constraints
            if (!$constraints) {
                continue;
            }

            $groups = self::getValidationGroups($config);
            $this->validateValuesBag($values, $constraints, $groups);
        }
    }

    /**
     * @param ValuesBag               $valuesBag
     * @param Constraint[]|Constraint $constraints
     */
    private function validateValuesBag(ValuesBag $valuesBag, $constraints, $validationGroups = null)
    {
        foreach ($valuesBag->getSingleValues() as $i => $value) {
            $this->validateValue(
                $value->getValue(),
                $value->getViewValue(),
                $constraints,
                'singleValues['.$i.'].value',
                $valuesBag,
                $validationGroups
            );
        }

        foreach ($valuesBag->getExcludedValues() as $i => $value) {
            $this->validateValue(
                $value->getValue(),
                $value->getViewValue(),
                $constraints,
                'excludedValues['.$i.'].value',
                $valuesBag,
                $validationGroups
            );
        }

        foreach ($valuesBag->getRanges() as $i => $value) {
            $this->validateRange(
                $value,
                $constraints,
                'ranges['.$i.']',
                $valuesBag,
                $validationGroups
            );
        }

        foreach ($valuesBag->getExcludedRanges() as $i => $value) {
            $this->validateRange(
                $value,
                $constraints,
                'excludedRanges['.$i.']',
                $valuesBag,
                $validationGroups
            );
        }

        foreach ($valuesBag->getComparisons() as $i => $value) {
            $this->validateValue(
                $value->getValue(),
                $value->getViewValue(),
                $constraints,
                'comparisons['.$i.'].value',
                $valuesBag,
                $validationGroups
            );
        }

        foreach ($valuesBag->getPatternMatchers() as $i => $value) {
            $this->validateValue(
                $value->getValue(),
                $value->getValue(),
                $constraints,
                'patternMatchers['.$i.'].value',
                $valuesBag,
                $validationGroups
            );
        }
    }

    /**
     * @param Range                   $range
     * @param Constraint[]|Constraint $constraints
     * @param string                  $subPath
     * @param ValuesBag               $valuesBag
     * @param string[]                $validationGroups
     */
    private function validateRange(Range $range, $constraints, $subPath, ValuesBag $valuesBag, $validationGroups = null)
    {
        $this->validateValue($range->getLower(), $range->getViewLower(), $constraints, $subPath.'.lower', $valuesBag, $validationGroups);
        $this->validateValue($range->getUpper(), $range->getViewUpper(), $constraints, $subPath.'.upper', $valuesBag, $validationGroups);
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
        $groups = $field->getOption('validation_groups', [Constraint::DEFAULT_GROUP]);

        if (!is_string($groups) && is_callable($groups)) {
            $groups = call_user_func($groups, $field);
        }

        return (array) $groups;
    }

    /**
     * @param mixed                   $value
     * @param string                  $viewValue
     * @param Constraint[]|Constraint $constraints
     * @param string                  $subPath
     * @param ValuesBag               $valuesBag
     * @param string[]                $validationGroups
     */
    private function validateValue($value, $viewValue, $constraints, $subPath, ValuesBag $valuesBag, $validationGroups = null)
    {
        if ($this->validator instanceof LegacyValidator) {
            $violations = $this->validator->validateValue($value, $constraints, $validationGroups);
        } else {
            $violations = $this->validator->validate($value, $constraints, $validationGroups);
        }

        foreach ($violations as $violation) {
            $valuesBag->addError($this->createError($violation, $viewValue, $subPath));
        }
    }

    /**
     * @param ConstraintViolation $violation
     * @param string              $viewValue
     * @param string              $subPath
     *
     * @return ValuesError
     */
    private function createError(ConstraintViolation $violation, $viewValue, $subPath)
    {
        static $newApi;

        if (null === $newApi) {
            $newApi = method_exists($violation, 'getPlural');
        }

        // Compatibility layer for Symfony 3.0
        if ($newApi) {
            $parameters = $violation->getParameters();
            $plural = $violation->getPlural();
        } else {
            $parameters = $violation->getMessageParameters();
            $plural = $violation->getMessagePluralization();
        }

        if ('' !== $viewValue && isset($parameters['{{ value }}'])) {
            if ('"' === $parameters['{{ value }}'][0]) {
                $viewValue = '"'.$viewValue.'"';
            }

            $parameters = array_merge($parameters, ['{{ value }}' => $viewValue]);
        }

        return new ValuesError(
            $subPath,
            $violation->getMessage(),
            $violation->getMessageTemplate(),
            $parameters,
            $plural,
            $violation
        );
    }
}
