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
use Rollerworks\Component\Search\Exception\InvalidArgumentException;
use Rollerworks\Component\Search\Exception\TransformationFailedException;
use Rollerworks\Component\Search\Exception\UnsupportedValueTypeException;
use Rollerworks\Component\Search\Exception\ValuesOverflowException;
use Rollerworks\Component\Search\Field\FieldConfig;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\ExcludedRange;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\ValuesBag;
use Rollerworks\Component\Search\ValueComparator;

/**
 * The FieldValuesFactory works as a wrapper around the ValuesBag
 * transforming input and ensuring restrictions are honored.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class FieldValuesFactory
{
    /**
     * @var FieldConfig
     */
    protected $config;

    /**
     * @var DataTransformer|null
     */
    protected $normTransformer;

    /**
     * @var DataTransformer|null
     */
    protected $viewTransformer;

    private $errorList;
    private $maxCount;
    private $count = 0;
    private $checkedValueType = [];
    private $validator;

    /**
     * @var ValuesBag
     */
    private $valuesBag;

    /**
     * @var ValueComparator
     */
    private $valueComparator;

    /**
     * @var string
     */
    private $path;

    public function __construct(ErrorList $errorList, Validator $validator, int $maxCount = 100)
    {
        $this->errorList = $errorList;
        $this->maxCount = $maxCount;
        $this->validator = $validator;
    }

    public function initContext(FieldConfig $field, ValuesBag $valuesBag, string $path): void
    {
        $this->config = $field;
        $this->valuesBag = $valuesBag;
        $this->count = $valuesBag->count();
        $this->path = $path;

        $this->valueComparator = $field->getValueComparator();
        $this->viewTransformer = $field->getViewTransformer();
        $this->normTransformer = $field->getNormTransformer() ?? $this->viewTransformer;

        $this->validator->initializeContext($field, $this->errorList);
    }

    public function addSimpleValue($value, string $path): void
    {
        $path = $this->createValuePath($path);

        $this->increaseValuesCount($path);

        if (null !== ($modelVal = $this->inputToNorm($value, $path)) &&
            $this->validator->validate($modelVal, 'simple', $value, $path)
        ) {
            $this->valuesBag->addSimpleValue($modelVal);
        }
    }

    public function addExcludedSimpleValue($value, string $path): void
    {
        $path = $this->createValuePath($path);
        $this->increaseValuesCount($path);

        if (null !== ($modelVal = $this->inputToNorm($value, $path)) &&
            $this->validator->validate($modelVal, 'excluded-simple', $value, $path)
        ) {
            $this->valuesBag->addExcludedSimpleValue($modelVal);
        }
    }

    /**
     * @param mixed $lower
     * @param mixed $upper
     * @param bool  $lowerInclusive
     * @param bool  $upperInclusive
     * @param array $path           [path, lower-path-pattern, upper-path-pattern]
     */
    public function addRange($lower, $upper, bool $lowerInclusive, bool $upperInclusive, array $path): void
    {
        $path[0] = $this->createValuePath($path[0]);

        $this->increaseValuesCount($path[0]);
        $this->assertAcceptsType(Range::class);

        $lowerNorm = $this->inputToNorm($lower, $path[0].$path[1]);
        $upperNorm = $this->inputToNorm($upper, $path[0].$path[2]);

        if (null !== $lowerNorm && null !== $upperNorm) {
            $range = new Range($lowerNorm, $upperNorm, $lowerInclusive, $upperInclusive);

            if ($this->validateRangeBounds($range, $path, $lower, $upper)) {
                $this->valuesBag->add($range);
            }
        }
    }

    /**
     * @param mixed $lower
     * @param mixed $upper
     * @param bool  $lowerInclusive
     * @param bool  $upperInclusive
     * @param array $path           [path, lower-path-pattern, upper-path-pattern]
     */
    public function addExcludedRange($lower, $upper, bool $lowerInclusive, bool $upperInclusive, array $path): void
    {
        $path[0] = $this->createValuePath($path[0]);

        $this->increaseValuesCount($path[0]);
        $this->assertAcceptsType(Range::class);

        $lowerNorm = $this->inputToNorm($lower, $path[0].$path[1]);
        $upperNorm = $this->inputToNorm($upper, $path[0].$path[2]);

        if (null !== $lowerNorm && null !== $upperNorm) {
            $range = new ExcludedRange($lowerNorm, $upperNorm, $lowerInclusive, $upperInclusive);

            if ($this->validateRangeBounds($range, $path, $lower, $upper)) {
                $this->valuesBag->add($range);
            }
        }
    }

    public function addComparisonValue($operator, $value, array $path): void
    {
        $basePath = $this->createValuePath($path[0]);

        $this->increaseValuesCount($basePath);
        $this->assertAcceptsType(Compare::class);

        $modelVal = $this->inputToNorm($value, $basePath.$path[2]);

        if (!in_array($operator, Compare::OPERATORS, true)) {
            $this->addError(
                ConditionErrorMessage::withMessageTemplate(
                    $basePath.$path[1],
                    'Unknown Comparison operator "{{ operator }}".',
                    ['{{ operator }}' => is_scalar($operator) ? $operator : gettype($operator)]
                )
            );
        } elseif (null !== $modelVal && $this->validator->validate($modelVal, Compare::class, $value, $basePath.$path[2])) {
            $this->valuesBag->add(new Compare($modelVal, $operator));
        }
    }

    public function addPatterMatch($type, $value, bool $caseInsensitive, array $path): void
    {
        $basePath = $this->createValuePath($path[0]);
        $valid = true;

        $this->increaseValuesCount($basePath);
        $this->assertAcceptsType(PatternMatch::class);

        if (!is_scalar($value)) {
            $this->addError(new ConditionErrorMessage($basePath.$path[1], 'PatternMatch value must a string.'));

            $valid = false;
        }

        if (!is_string($type)) {
            $this->addError(new ConditionErrorMessage($basePath.$path[2], 'PatternMatch type must a string.'));

            $valid = false;
        }

        if (!$valid) {
            return;
        }

        try {
            $patternMatch = new PatternMatch((string) $value, $type, $caseInsensitive);

            if (!$this->validator->validate($value, PatternMatch::class, $value, $basePath.$path[1])) {
                return;
            }

            $this->valuesBag->add($patternMatch);
        } catch (InvalidArgumentException $e) {
            $this->addError(
                ConditionErrorMessage::withMessageTemplate(
                    $basePath.$path[2],
                    'Unknown PatternMatch type "{{ type }}".',
                    ['{{ type }}' => $type],
                    null,
                    $e
                )
            );
        }
    }

    /**
     * Reverse transforms a value if a value transformer is set.
     *
     * @param mixed  $value The value to reverse transform
     * @param string $path
     *
     * @return mixed Returns null when the value is empty or invalid
     */
    protected function inputToNorm($value, string $path)
    {
        if (!$this->normTransformer) {
            if (null !== $value && !is_scalar($value)) {
                $e = new \RuntimeException(
                    sprintf(
                        'Norm value of type %s is not a scalar value or null and not cannot be '.
                        'converted to a string. You must set a NormTransformer for field "%s" with type "%s".',
                        gettype($value),
                        $this->config->getName(),
                        get_class($this->config->getType()->getInnerType())
                    )
                );

                $error = new ConditionErrorMessage(
                    $path,
                    $this->config->getOption('invalid_message', $e->getMessage()),
                    $this->config->getOption('invalid_message', $e->getMessage()),
                    $this->config->getOption('invalid_message_parameters', []),
                    null,
                    $e
                );

                $this->addError($error);

                return null;
            }

            return '' === $value ? null : $value;
        }

        try {
            return $this->normTransformer->reverseTransform($value);
        } catch (TransformationFailedException $e) {
            $error = new ConditionErrorMessage(
                $path,
                $this->config->getOption('invalid_message', $e->getMessage()),
                $this->config->getOption('invalid_message', $e->getMessage()),
                $this->config->getOption('invalid_message_parameters', []),
                null,
                $e
            );

            $this->addError($error);

            return null;
        }
    }

    protected function addError(ConditionErrorMessage $error): void
    {
        $this->errorList[] = $error;
    }

    private function createValuePath(string $path): string
    {
        if (false !== mb_strpos($path, '%d')) {
            return $this->path.sprintf($path, $this->count);
        }

        return $this->path.$path;
    }

    private function increaseValuesCount(string $path): void
    {
        if (++$this->count > $this->maxCount) {
            throw new ValuesOverflowException($this->config->getName(), $this->maxCount, $path);
        }
    }

    private function assertAcceptsType(string $type): void
    {
        if (isset($this->checkedValueType[$type])) {
            return;
        }

        if (!$this->config->supportValueType($type)) {
            throw new UnsupportedValueTypeException($this->config->getName(), $type);
        }

        $this->checkedValueType[$type] = true;
    }

    private function validateRangeBounds(Range $range, array $path, $lower, $upper): bool
    {
        if (!$this->valueComparator->isLower($range->getLower(), $range->getUpper(), $this->config->getOptions())) {
            $message = 'Lower range-value {{ lower }} should be lower then upper range-value {{ upper }}.';
            $params = [
                '{{ lower }}' => mb_strpos((string) $lower, ' ') ? "'".$lower."'" : $lower,
                '{{ upper }}' => mb_strpos((string) $upper, ' ') ? "'".$upper."'" : $upper,
            ];

            $this->addError(ConditionErrorMessage::withMessageTemplate($path[0], $message, $params));

            return false;
        }

        $class = get_class($range);

        // Perform validation for both bounds (don't move to condition as this returns early).
        $lowerValid = $this->validator->validate($range->getLower(), $class, $lower, $path[0].$path[1]);
        $upperValid = $this->validator->validate($range->getUpper(), $class, $upper, $path[0].$path[2]);

        return $lowerValid && $upperValid;
    }
}
