<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Input;

use Rollerworks\Component\Search\Exception\TransformationFailedException;
use Rollerworks\Component\Search\Exception\UnsupportedValueTypeException;
use Rollerworks\Component\Search\Exception\ValuesOverflowException;
use Rollerworks\Component\Search\FieldConfigInterface;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\SingleValue;
use Rollerworks\Component\Search\ValuesBag;
use Rollerworks\Component\Search\ValuesError;

/**
 * The FieldValuesFactory works as a wrapper around the ValuesBag
 * transforming input and ensuring limits are honored.
 */
final class FieldValuesFactory
{
    private $config;
    private $valuesBag;
    private $maxCount;
    private $groupIdx;
    private $level;
    private $count = 0;
    private $checkedValueType = [];

    public function __construct(
        FieldConfigInterface $fieldConfig,
        ValuesBag $valuesBag,
        $maxCount = 100,
        $groupIdx = 0,
        $level = 0
    ) {
        $this->config = $fieldConfig;
        $this->valuesBag = $valuesBag;
        $this->maxCount = $maxCount;
        $this->groupIdx = $groupIdx;
        $this->level = $level;

        $this->count = $valuesBag->count();
    }

    public function addSingleValue($value)
    {
        if (++$this->count > $this->maxCount) {
            $this->throwValuesOverflow();
        }

        $path = 'singleValues['.count($this->valuesBag->getSingleValues()).']';

        $normValue = $this->viewToNorm($value, $path);
        $viewValue = $this->normToView($normValue, $path);

        if (null === $normValue || null === $viewValue) {
            $singleValue = new SingleValue($value);
        } else {
            $singleValue = new SingleValue($normValue, $viewValue);
        }

        $this->valuesBag->addSingleValue($singleValue);
    }

    public function addExcludedValue($value)
    {
        if (++$this->count > $this->maxCount) {
            $this->throwValuesOverflow();
        }

        $path = 'excludedValues['.count($this->valuesBag->getExcludedValues()).']';

        $normValue = $this->viewToNorm($value, $path);
        $viewValue = $this->normToView($normValue, $path);

        if (null === $normValue || null === $viewValue) {
            $singleValue = new SingleValue($value);
        } else {
            $singleValue = new SingleValue($normValue, $viewValue);
        }

        $this->valuesBag->addExcludedValue($singleValue);
    }

    public function addRange($lower, $upper, $lowerInclusive, $upperInclusive)
    {
        if (++$this->count > $this->maxCount) {
            $this->throwValuesOverflow();
        }

        $this->assertAcceptsType('range');

        $path = 'ranges['.count($this->valuesBag->getRanges()).']';

        $this->valuesBag->addRange(
            $this->createRangeValue($lower, $upper, $lowerInclusive, $upperInclusive, $path)
        );
    }

    public function addExcludedRange($lower, $upper, $lowerInclusive, $upperInclusive)
    {
        if (++$this->count > $this->maxCount) {
            $this->throwValuesOverflow();
        }

        $this->assertAcceptsType('range');

        $path = 'excludedRanges['.count($this->valuesBag->getExcludedRanges()).']';

        $this->valuesBag->addExcludedRange(
            $this->createRangeValue($lower, $upper, $lowerInclusive, $upperInclusive, $path)
        );
    }

    public function addComparisonValue($operator, $value)
    {
        if (++$this->count > $this->maxCount) {
            $this->throwValuesOverflow();
        }

        $this->assertAcceptsType('comparison');

        $path = 'comparisons['.count($this->valuesBag->getComparisons()).'].value';

        $normValue = $this->viewToNorm($value, $path);
        $viewValue = $this->normToView($normValue, $path);

        if (null === $normValue || null === $viewValue) {
            $comparison = new Compare($value, $operator);
        } else {
            $comparison = new Compare($normValue, $operator, $viewValue);
        }

        $this->valuesBag->addComparison($comparison);
    }

    public function addPatterMatch($type, $patternMatch, $caseInsensitive)
    {
        if (++$this->count > $this->maxCount) {
            $this->throwValuesOverflow();
        }

        $this->assertAcceptsType('pattern-match');

        if (!is_scalar($patternMatch)) {
            throw new \RuntimeException(
                sprintf(
                    'Pattern-match value %s is not a scalar value and not cannot be converted to a string.',
                    gettype($patternMatch)
                )
            );
        }

        $this->valuesBag->addPatternMatch(
            new PatternMatch((string) $patternMatch, $type, $caseInsensitive)
        );
    }

    private function throwValuesOverflow()
    {
        throw new ValuesOverflowException(
            $this->config->getName(), $this->maxCount, $this->groupIdx, $this->level
        );
    }

    private function createRangeValue($lower, $upper, $lowerInclusive, $upperInclusive, $path)
    {
        $lowerNorm = $this->viewToNorm($lower, $path.'.lower');
        $lowerView = $this->normToView($lowerNorm, $path.'.lower');

        $upperNorm = $this->viewToNorm($upper, $path.'.upper');
        $upperView = $this->normToView($upperNorm, $path.'.upper');

        if (null === $lowerNorm || null === $lowerView || null === $upperNorm || null === $upperView) {
            return new Range($lower, $upper, $lowerInclusive, $upperInclusive);
        }

        $range = new Range($lowerNorm, $upperNorm, $lowerInclusive, $upperInclusive, $lowerView, $upperView);
        $this->validateRangeBounds($range, $path);

        return $range;
    }

    private function assertAcceptsType($type)
    {
        if (isset($this->checkedValueType[$type])) {
            return;
        }

        if (!$this->config->supportValueType($type)) {
            throw new UnsupportedValueTypeException($this->config->getName(), $type);
        }

        $this->checkedValueType[$type] = true;
    }

    private function validateRangeBounds(Range $range, $path)
    {
        if (!$this->config->getValueComparison()->isLower(
            $range->getLower(),
            $range->getUpper(),
            $this->config->getOptions()
        )) {
            $lowerValue = $range->getViewLower();
            $upperValue = $range->getViewUpper();

            $message = 'Lower range-value {{ lower }} should be lower then upper range-value {{ upper }}.';
            $params = [
                '{{ lower }}' => strpos($lowerValue, ' ') ? "'".$lowerValue."'" : $lowerValue,
                '{{ upper }}' => strpos($upperValue, ' ') ? "'".$upperValue."'" : $upperValue,
            ];

            $this->valuesBag->addError(
                new ValuesError($path, strtr($message, $params), $message, $params)
            );
        }
    }

    /**
     * Transforms the value if a value transformer is set.
     *
     * @param mixed  $value The value to transform
     * @param string $path
     *
     * @return string|null Returns null when the value is empty or invalid
     */
    private function normToView($value, $path)
    {
        // Scalar values should be converted to strings to
        // facilitate differentiation between empty ("") and zero (0).
        if (null === $value || !$this->config->getViewTransformers()) {
            if (null !== $value && !is_scalar($value)) {
                throw new \RuntimeException(
                    sprintf(
                        'Norm value of type %s is not a scalar value or null and not cannot be '.
                        'converted to a string. You must set a viewTransformer for field "%s" with type "%s".',
                        gettype($value),
                        $this->config->getName(),
                        $this->config->getType()->getName()
                    )
                );
            }

            return $value;
        }

        try {
            foreach ($this->config->getViewTransformers() as $transformer) {
                $value = $transformer->transform($value);
            }

            return $value;
        } catch (TransformationFailedException $e) {
            $this->valuesBag->addError(
                new ValuesError(
                    $path,
                    $this->config->getOption('invalid_message', $e->getMessage()),
                    $this->config->getOption('invalid_message', $e->getMessage()),
                    $this->config->getOption('invalid_message_parameters', []),
                    null,
                    $e
                )
            );
        }
    }

    /**
     * Reverse transforms a value if a value transformer is set.
     *
     * @param string $value The value to reverse transform
     * @param string $path
     *
     * @return mixed Returns null when the value is empty or invalid
     */
    private function viewToNorm($value, $path)
    {
        $transformers = $this->config->getViewTransformers();

        if (!$transformers) {
            return '' === $value ? null : $value;
        }

        try {
            for ($i = count($transformers) - 1; $i >= 0; --$i) {
                $value = $transformers[$i]->reverseTransform($value);
            }

            return $value;
        } catch (TransformationFailedException $e) {
            $this->valuesBag->addError(
                new ValuesError(
                    $path,
                    $this->config->getOption('invalid_message', $e->getMessage()),
                    $this->config->getOption('invalid_message', $e->getMessage()),
                    $this->config->getOption('invalid_message_parameters', []),
                    null,
                    $e
                )
            );
        }
    }
}
