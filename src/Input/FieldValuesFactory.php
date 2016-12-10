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
use Rollerworks\Component\Search\ErrorList;
use Rollerworks\Component\Search\Exception\TransformationFailedException;
use Rollerworks\Component\Search\Exception\UnsupportedValueTypeException;
use Rollerworks\Component\Search\Exception\ValuesOverflowException;
use Rollerworks\Component\Search\FieldConfigInterface;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\ExcludedRange;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\ValuesBag;

/**
 * The FieldValuesFactory works as a wrapper around the ValuesBag
 * transforming input and ensuring limits are honored.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class FieldValuesFactory
{
    protected $config;
    protected $fieldName;
    protected $viewTransformer;
    protected $normTransformer;

    private $path;
    private $valuesBag;
    private $errorList;
    private $count = 0;
    private $maxCount;
    private $checkedValueType = [];
    private $valueComparison;

    public function __construct(FieldConfigInterface $field, ValuesBag $valuesBag, ErrorList $errorList, string $path, int $maxCount = 100)
    {
        $this->config = $field;
        $this->valueComparison = $field->getValueComparison();
        $this->fieldName = $this->config->getName();
        $this->valuesBag = $valuesBag;

        $this->path = $path;
        $this->errorList = $errorList;
        $this->count = $valuesBag->count();
        $this->maxCount = $maxCount;

        $this->viewTransformer = $field->getViewTransformer();
        $this->normTransformer = $field->getNormTransformer() ?: $this->viewTransformer;
    }

    public function addSimpleValue($value, string $path)
    {
        $path = $this->createValuePath($path);

        $this->increaseValuesCount($path);

        if (null !== $modelVal = $this->inputToNorm($value, $path)) {
            $this->valuesBag->addSimpleValue($modelVal);
        }
    }

    public function addExcludedSimpleValue($value, string $path)
    {
        $path = $this->createValuePath($path);
        $this->increaseValuesCount($path);

        if (null !== $modelVal = $this->inputToNorm($value, $path)) {
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
    public function addRange($lower, $upper, bool $lowerInclusive, bool $upperInclusive, array $path)
    {
        $basePath = $this->createValuePath($path[0]);

        $this->increaseValuesCount($basePath);
        $this->assertAcceptsType(Range::class);

        $lowerNorm = $this->inputToNorm($lower, $basePath.$path[1]);
        $upperNorm = $this->inputToNorm($upper, $basePath.$path[2]);

        if (null !== $lowerNorm && null !== $upperNorm) {
            $range = new Range($lowerNorm, $upperNorm, $lowerInclusive, $upperInclusive);

            $this->validateRangeBounds($range, $basePath, $lower, $upper);
            $this->valuesBag->add($range);
        }
    }

    /**
     * @param mixed $lower
     * @param mixed $upper
     * @param bool  $lowerInclusive
     * @param bool  $upperInclusive
     * @param array $path           [path, lower-path-pattern, upper-path-pattern]
     */
    public function addExcludedRange($lower, $upper, $lowerInclusive, $upperInclusive, array $path)
    {
        $basePath = $this->createValuePath($path[0]);

        $this->increaseValuesCount($basePath);
        $this->assertAcceptsType(Range::class);

        $lowerNorm = $this->inputToNorm($lower, $basePath.$path[1]);
        $upperNorm = $this->inputToNorm($upper, $basePath.$path[2]);

        if (null !== $lowerNorm && null !== $upperNorm) {
            $range = new ExcludedRange($lowerNorm, $upperNorm, $lowerInclusive, $upperInclusive);

            $this->validateRangeBounds($range, $basePath, $lower, $upper);
            $this->valuesBag->add($range);
        }
    }

    public function addComparisonValue($operator, $value, array $path)
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
        } elseif (null !== $modelVal) {
            $this->valuesBag->add(new Compare($modelVal, $operator));
        }
    }

    public function addPatterMatch($type, $patternMatch, $caseInsensitive, array $path)
    {
        $basePath = $this->createValuePath($path[0]);
        $valid = true;

        $this->increaseValuesCount($basePath);
        $this->assertAcceptsType(PatternMatch::class);

        if (!is_scalar($patternMatch)) {
            $this->addError(new ConditionErrorMessage($basePath.$path[1], 'PatternMatch value must a string.'));

            $valid = false;
        }

        if (!is_string($type)) {
            $this->addError(new ConditionErrorMessage($basePath.$path[2], 'PatternMatch type must a string.'));

            $valid = false;
        } elseif (!defined(PatternMatch::class.'::PATTERN_'.strtoupper($type))) {
            $this->addError(
                ConditionErrorMessage::withMessageTemplate(
                    $basePath.$path[2],
                    'Unknown PatternMatch type "{{ type }}".',
                    ['{{ type }}' => $type]
                )
            );

            $valid = false;
        }

        if (false === $valid) {
            return;
        }

        $this->valuesBag->add(new PatternMatch((string) $patternMatch, $type, $caseInsensitive));
    }

    /**
     * Reverse transforms a value if a value transformer is set.
     *
     * @param string $value The value to reverse transform
     * @param string $path
     *
     * @return mixed Returns null when the value is empty or invalid
     */
    protected function inputToNorm($value, string $path)
    {
        if (!$this->normTransformer) {
            if (null !== $value && !is_scalar($value)) {
                throw new \RuntimeException(
                    sprintf(
                        'Norm value of type %s is not a scalar value or null and not cannot be '.
                        'converted to a string. You must set a NormTransformer for field "%s" with type "%s".',
                        gettype($value),
                        $this->fieldName,
                        get_class($this->config->getType()->getInnerType())
                    )
                );
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

            return;
        }
    }

    protected function addError(ConditionErrorMessage $error)
    {
        $this->errorList[] = $error;
    }

    private function createValuePath(string $path): string
    {
        if (false !== strpos($path, '%d')) {
            return $this->path.sprintf($path, $this->count);
        }

        return $this->path.$path;
    }

    private function increaseValuesCount(string $path)
    {
        if (++$this->count > $this->maxCount) {
            throw new ValuesOverflowException($this->fieldName, $this->maxCount, $path);
        }
    }

    private function assertAcceptsType(string $type)
    {
        if (isset($this->checkedValueType[$type])) {
            return;
        }

        if (!$this->config->supportValueType($type)) {
            throw new UnsupportedValueTypeException($this->fieldName, $type);
        }

        $this->checkedValueType[$type] = true;
    }

    private function validateRangeBounds(Range $range, $path, $lower, $upper)
    {
        if (!$this->valueComparison->isLower($range->getLower(), $range->getUpper(), $this->config->getOptions())) {
            $message = 'Lower range-value {{ lower }} should be lower then upper range-value {{ upper }}.';
            $params = [
                '{{ lower }}' => strpos((string) $lower, ' ') ? "'".$lower."'" : $lower,
                '{{ upper }}' => strpos((string) $upper, ' ') ? "'".$upper."'" : $upper,
            ];

            $this->addError(ConditionErrorMessage::withMessageTemplate($path, $message, $params));
        }
    }
}
