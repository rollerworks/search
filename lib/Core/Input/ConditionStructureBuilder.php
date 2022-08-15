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

namespace Rollerworks\Component\Search\Input;

use Rollerworks\Component\Search\ConditionErrorMessage;
use Rollerworks\Component\Search\DataTransformer;
use Rollerworks\Component\Search\ErrorList;
use Rollerworks\Component\Search\Exception\GroupsNestingException;
use Rollerworks\Component\Search\Exception\GroupsOverflowException;
use Rollerworks\Component\Search\Exception\InvalidArgumentException;
use Rollerworks\Component\Search\Exception\TransformationFailedException;
use Rollerworks\Component\Search\Exception\UnsupportedValueTypeException;
use Rollerworks\Component\Search\Exception\ValuesOverflowException;
use Rollerworks\Component\Search\Field\FieldConfig;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\StructureBuilder;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\ExcludedRange;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\ValuesBag;
use Rollerworks\Component\Search\Value\ValuesGroup;

/**
 * Works as a wrapper around the ValuesGroup, and ValuesBag transforming
 * input while ensuring restrictions are honored.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class ConditionStructureBuilder implements StructureBuilder
{
    /** @var ErrorList */
    private $errorList;

    /** @var Validator */
    private $validator;

    /** @var FieldSet */
    private $fieldSet;

    /** @var int */
    private $maxCount;

    /** @var int */
    private $maxNesting;

    /** @var int */
    private $maxGroups;

    /** @var array */
    private $checkedValueType = [];

    /** @var int */
    private $valuesCount = 0;

    /** @var int */
    private $nestingLevel = 0;

    /** @var array */
    private $path = [];

    /**
     * Group count per nesting level.
     *
     * @var array
     */
    private $groupsCount = [];

    /** @var FieldConfig|null */
    protected $fieldConfig;

    /** @var ValuesGroup[] */
    private $valuesGroupLevels = [];

    /** @var ValuesBag|null */
    private $valuesBag;

    /**
     * False when not set, null when undetected (lazy loaded).
     *
     * @var bool|DataTransformer|null
     */
    protected $inputTransformer;

    public function __construct(ProcessorConfig $config, Validator $validator, ErrorList $errorList, string $path = '')
    {
        $this->validator = $validator;
        $this->fieldSet = $config->getFieldSet();
        $this->maxCount = $config->getMaxValues();
        $this->maxNesting = $config->getMaxNestingLevel();
        $this->maxGroups = $config->getMaxGroups();

        $this->errorList = $errorList ?? new ErrorList();
        $this->valuesGroupLevels[0] = new ValuesGroup();
        $this->path[] = $path;
    }

    public function getErrors(): ErrorList
    {
        return $this->errorList;
    }

    public function getCurrentPath()
    {
        return $this->path;
    }

    public function getRootGroup(): ValuesGroup
    {
        return $this->valuesGroupLevels[0];
    }

    public function enterGroup(string $groupLocal = 'AND', string $path = '[%d]'): void
    {
        if (! isset($this->groupsCount[$this->nestingLevel])) {
            $this->groupsCount[$this->nestingLevel] = 1;
        } else {
            ++$this->groupsCount[$this->nestingLevel];
        }

        $groupCount = $this->groupsCount[$this->nestingLevel];

        if ($groupCount > $this->maxGroups) {
            throw new GroupsOverflowException($this->maxGroups, implode('', $this->path));
        }

        // The new group is relative to it's current level (the group is declared at level x); and zero-indexed
        $this->path[] = sprintf($path, $groupCount - 1);

        ++$this->nestingLevel;

        if ($this->nestingLevel > $this->maxNesting) {
            throw new GroupsNestingException($this->maxNesting, implode('', $this->path));
        }

        $this->valuesGroupLevels[$this->nestingLevel - 1]->addGroup(
            $this->valuesGroupLevels[$this->nestingLevel] = new ValuesGroup($groupLocal)
        );
    }

    public function leaveGroup(): void
    {
        if ($this->fieldConfig !== null) {
            $this->endValues();
        }

        --$this->nestingLevel;
        array_pop($this->path);
    }

    public function field(string $name, string $path): void
    {
        $this->checkedValueType = [];
        $this->valuesCount = 0;

        $this->fieldConfig = $this->fieldSet->get($name);
        $this->valuesBag = new ValuesBag();

        $this->valuesGroupLevels[$this->nestingLevel]->addField($name, $this->valuesBag);
        $this->path[] = sprintf($path, $name);

        $this->validator->initializeContext($this->fieldConfig, $this->errorList);
    }

    public function simpleValue($value, string $path): void
    {
        $path = $this->createValuePath($path, 'simpleValue');
        $this->increaseValuesCount($path);

        if (($modelVal = $this->inputToNorm($value, $path)) !== null) {
            $this->validator->validate($modelVal, 'simple', $value, $path);
        }
        $this->valuesBag->addSimpleValue($modelVal);
    }

    public function excludedSimpleValue($value, string $path): void
    {
        $path = $this->createValuePath($path, 'excludedSimpleValue');
        $this->increaseValuesCount($path);

        if (($modelVal = $this->inputToNorm($value, $path)) !== null) {
            $this->validator->validate($modelVal, 'excluded-simple', $value, $path);
        }
        $this->valuesBag->addExcludedSimpleValue($modelVal);
    }

    /**
     * @param array $path [path, lower-path-pattern, upper-path-pattern]
     */
    public function rangeValue($lower, $upper, bool $lowerInclusive, bool $upperInclusive, array $path): void
    {
        $path[0] = $this->createValuePath($path[0], Range::class);

        $this->increaseValuesCount($path[0]);
        $this->assertAcceptsType(Range::class);

        $lowerNorm = $this->inputToNorm($lower, $path[0] . $path[1]);
        $upperNorm = $this->inputToNorm($upper, $path[0] . $path[2]);

        $range = new Range($lowerNorm, $upperNorm, $lowerInclusive, $upperInclusive);

        if ($lowerNorm !== null && $upperNorm !== null) {
            $this->validateRangeBounds($range, $path, $lower, $upper);
        }
        $this->valuesBag->add($range);
    }

    /**
     * @param array $path [path, lower-path-pattern, upper-path-pattern]
     */
    public function excludedRangeValue($lower, $upper, bool $lowerInclusive, bool $upperInclusive, array $path): void
    {
        $path[0] = $this->createValuePath($path[0], ExcludedRange::class);

        $this->increaseValuesCount($path[0]);
        $this->assertAcceptsType(Range::class);

        $lowerNorm = $this->inputToNorm($lower, $path[0] . $path[1]);
        $upperNorm = $this->inputToNorm($upper, $path[0] . $path[2]);

        $range = new ExcludedRange($lowerNorm, $upperNorm, $lowerInclusive, $upperInclusive);

        if ($lowerNorm !== null && $upperNorm !== null) {
            $this->validateRangeBounds($range, $path, $lower, $upper);
        }
        $this->valuesBag->add($range);
    }

    /**
     * @param mixed|string $operator
     * @param array        $path     [base-path, operator-path, value-path]
     */
    public function comparisonValue($operator, $value, array $path): void
    {
        $path[0] = $this->createValuePath($path[0], Compare::class);

        $this->increaseValuesCount($path[0]);
        $this->assertAcceptsType(Compare::class);

        $modelVal = $this->inputToNorm($value, $path[0] . $path[2]);

        if (! \in_array($operator, Compare::OPERATORS, true)) {
            $this->addError(
                ConditionErrorMessage::withMessageTemplate(
                    $path[0] . $path[1],
                    'Unknown Comparison operator "{{ operator }}".',
                    ['{{ operator }}' => \is_scalar($operator) ? $operator : \gettype($operator)]
                )
            );
            $operator = '<>';
        } elseif ($modelVal !== null) {
            $this->validator->validate($modelVal, Compare::class, $value, $path[0] . $path[2]);
        }

        $this->valuesBag->add(new Compare($modelVal, $operator));
    }

    /**
     * @param string $type
     * @param string $value
     * @param array  $path  [base-path, value-path, type-path]
     */
    public function patterMatchValue($type, $value, bool $caseInsensitive, array $path): void
    {
        $path[0] = $this->createValuePath($path[0], PatternMatch::class);
        $valid = true;

        $this->increaseValuesCount($path[0]);
        $this->assertAcceptsType(PatternMatch::class);

        if (! \is_scalar($value)) {
            $this->addError(new ConditionErrorMessage($path[0] . $path[1], 'PatternMatch value must a string.'));

            $valid = false;
        }

        if (! \is_string($type)) {
            $this->addError(new ConditionErrorMessage($path[0] . $path[2], 'PatternMatch type must a string.'));

            $valid = false;
        }

        if (! $valid) {
            return;
        }

        try {
            $patternMatch = new PatternMatch((string) $value, $type, $caseInsensitive);

            if (! $this->validator->validate($value, PatternMatch::class, $value, $path[0] . $path[1])) {
                return;
            }

            $this->valuesBag->add($patternMatch);
        } catch (InvalidArgumentException $e) {
            $this->addError(
                ConditionErrorMessage::withMessageTemplate(
                    $path[0] . $path[2],
                    'Unknown PatternMatch type "{{ type }}".',
                    ['{{ type }}' => $type],
                    null,
                    $e
                )
            );
        }
    }

    public function endValues(): void
    {
        $this->fieldConfig = null;
        $this->valuesBag = null;
        $this->inputTransformer = null;

        array_pop($this->path);
    }

    /**
     * Reverse transforms a value if a value transformer is set.
     *
     * @return mixed returns null when the value is empty or invalid.
     *               Note: When the value is invalid an error is registered
     */
    protected function inputToNorm($value, string $path)
    {
        if ($this->inputTransformer === null) {
            $this->inputTransformer = $this->fieldConfig->getNormTransformer() ?? false;
        }

        if ($this->inputTransformer === false) {
            if ($value !== null && ! \is_scalar($value)) {
                $e = new \RuntimeException(
                    sprintf(
                        'Norm value of type %s is not a scalar value or null and not cannot be ' .
                        'converted to a string. You must set a NormTransformer for field "%s" with type "%s".',
                        \gettype($value),
                        $this->fieldConfig->getName(),
                        \get_class($this->fieldConfig->getType()->getInnerType())
                    )
                );

                $error = new ConditionErrorMessage(
                    $path,
                    $this->fieldConfig->getOption('invalid_message', $e->getMessage()),
                    $this->fieldConfig->getOption('invalid_message', $e->getMessage()),
                    $this->fieldConfig->getOption('invalid_message_parameters', []),
                    null,
                    $e
                );

                $this->addError($error);

                return null;
            }

            return $value === '' ? null : $value;
        }

        try {
            return $this->inputTransformer->reverseTransform($value);
        } catch (TransformationFailedException $e) {
            $this->addError($this->transformationExceptionToError($e, $path));

            return null;
        }
    }

    protected function transformationExceptionToError($e, string $path): ConditionErrorMessage
    {
        $invalidMessage = $e->getInvalidMessage();

        if ($invalidMessage !== null) {
            $error = new ConditionErrorMessage(
                $path,
                $invalidMessage,
                $invalidMessage,
                $e->getInvalidMessageParameters(),
                null,
                $e
            );
        } else {
            $error = new ConditionErrorMessage(
                $path,
                $this->fieldConfig->getOption('invalid_message', $e->getMessage()),
                $this->fieldConfig->getOption('invalid_message', $e->getMessage()),
                $this->fieldConfig->getOption('invalid_message_parameters', []),
                null,
                $e
            );
        }

        return $error;
    }

    protected function addError(ConditionErrorMessage $error): void
    {
        $this->errorList[] = $error;
    }

    private function createValuePath(string $path, string $type): string
    {
        if (mb_strpos($path, '{idx}') !== false) {
            $path = str_replace('{idx}', (string) $this->valuesBag->count($type), $path);
        } else {
            $path = str_replace('{pos}', (string) $this->valuesCount, $path);
        }

        return implode('', $this->path) . $path;
    }

    private function increaseValuesCount(string $path): void
    {
        if (++$this->valuesCount > $this->maxCount) {
            throw new ValuesOverflowException($this->fieldConfig->getName(), $this->maxCount, $path);
        }
    }

    private function assertAcceptsType(string $type): void
    {
        if (isset($this->checkedValueType[$type])) {
            return;
        }

        if (! $this->fieldConfig->supportValueType($type)) {
            throw new UnsupportedValueTypeException($this->fieldConfig->getName(), $type);
        }

        $this->checkedValueType[$type] = true;
    }

    private function validateRangeBounds(Range $range, array $path, $lower, $upper): void
    {
        if (! $this->fieldConfig->getValueComparator()->isLower($range->getLower(), $range->getUpper(), $this->fieldConfig->getOptions())) {
            $message = 'Lower range-value {{ lower }} should be lower then upper range-value {{ upper }}.';
            $params = [
                '{{ lower }}' => mb_strpos((string) $lower, ' ') ? "'" . $lower . "'" : $lower,
                '{{ upper }}' => mb_strpos((string) $upper, ' ') ? "'" . $upper . "'" : $upper,
            ];

            $this->addError(ConditionErrorMessage::withMessageTemplate($path[0], $message, $params));

            return;
        }

        $class = \get_class($range);

        // Perform validation for both bounds (don't move to bounds validator as that returns early).
        $this->validator->validate($range->getLower(), $class, $lower, $path[0] . $path[1]);
        $this->validator->validate($range->getUpper(), $class, $upper, $path[0] . $path[2]);
    }
}
