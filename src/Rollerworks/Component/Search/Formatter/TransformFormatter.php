<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search\Formatter;

use Rollerworks\Component\Search\Exception\TransformationFailedException;
use Rollerworks\Component\Search\FieldConfigInterface;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\FormatterInterface;
use Rollerworks\Component\Search\SearchConditionInterface;
use Rollerworks\Component\Search\ValuesBag;
use Rollerworks\Component\Search\ValuesError;
use Rollerworks\Component\Search\ValuesGroup;

/**
 * Transforms the values to a normalized format and view format.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class TransformFormatter implements FormatterInterface
{
    /**
     * {@inheritDoc}
     */
    public function format(SearchConditionInterface $condition)
    {
        $fieldSet = $condition->getFieldSet();
        $valuesGroup = $condition->getValuesGroup();

        $this->transformValuesInGroup($valuesGroup, $fieldSet);
    }

    /**
     * @param ValuesGroup $valuesGroup
     * @param FieldSet    $fieldSet
     */
    private function transformValuesInGroup(ValuesGroup $valuesGroup, FieldSet $fieldSet)
    {
        foreach ($valuesGroup->getGroups() as $group) {
            $this->transformValuesInGroup($group, $fieldSet);
        }

        foreach ($valuesGroup->getFields() as $fieldName => $values) {
            if (!$fieldSet->has($fieldName)) {
                continue;
            }

            $config = $fieldSet->get($fieldName);
            $this->transformValuesBag($config, $values);
        }
    }

    /**
     * @param FieldConfigInterface $config
     * @param ValuesBag            $valuesBag
     */
    private function transformValuesBag(FieldConfigInterface $config, ValuesBag $valuesBag)
    {
        $propertyPath = null;

        try {
            if ($valuesBag->hasSingleValues()) {
                foreach ($valuesBag->getSingleValues() as $i => $value) {
                    $propertyPath = "singleValues[$i]";

                    $value->setValue($this->viewToNorm($value->getViewValue(), $config));
                    $value->setViewValue($this->normToView($value->getValue(), $config));
                }
            }

            if ($valuesBag->hasExcludedValues()) {
                foreach ($valuesBag->getExcludedValues() as $i => $value) {
                    $propertyPath = "excludedValues[$i]";
                    $value->setValue($this->viewToNorm($value->getViewValue(), $config));
                    $value->setViewValue($this->normToView($value->getValue(), $config));
                }
            }

            if ($valuesBag->hasRanges()) {
                foreach ($valuesBag->getRanges() as $i => $value) {
                    $propertyPath = "ranges[$i].lower";
                    $value->setLower($this->viewToNorm($value->getViewLower(), $config));
                    $value->setViewLower($this->normToView($value->getLower(), $config));

                    $propertyPath = "ranges[$i].upper";
                    $value->setUpper($this->viewToNorm($value->getViewUpper(), $config));
                    $value->setViewUpper($this->normToView($value->getUpper(), $config));
                }
            }

            if ($valuesBag->hasExcludedRanges()) {
                foreach ($valuesBag->getExcludedRanges() as $i => $value) {
                    $propertyPath = "excludedRanges[$i].lower";
                    $value->setLower($this->viewToNorm($value->getViewLower(), $config));
                    $value->setViewLower($this->normToView($value->getLower(), $config));

                    $propertyPath = "excludedRanges[$i].upper";
                    $value->setUpper($this->viewToNorm($value->getViewUpper(), $config));
                    $value->setViewUpper($this->normToView($value->getUpper(), $config));
                }
            }

            if ($valuesBag->hasComparisons()) {
                foreach ($valuesBag->getComparisons() as $i => $value) {
                    $propertyPath = "comparisons[$i]";
                    $value->setValue($this->viewToNorm($value->getViewValue(), $config));
                    $value->setViewValue($this->normToView($value->getValue(), $config));
                }
            }

            if ($valuesBag->hasPatternMatchers()) {
                foreach ($valuesBag->getPatternMatchers() as $i => $value) {
                    // Only normalize when its not a regex, normalizing might break the regex pattern
                    if (!in_array($value->getType(), array($value::PATTERN_REGEX, $value::PATTERN_NOT_REGEX))) {
                        $propertyPath = "patternMatchers[$i]";
                        $value->setValue($this->viewToNorm($value->getViewValue(), $config));
                        $value->setViewValue($this->normToView($value->getValue(), $config));
                    }
                }
            }
        } catch (TransformationFailedException $e) {
            $valuesBag->addError(new ValuesError($propertyPath, $e->getMessage()));
        }
    }

    /**
     * Transforms the value if a value transformer is set.
     *
     * @param mixed                $value  The value to transform
     * @param FieldConfigInterface $config
     *
     * @return mixed
     */
    private function normToView($value, FieldConfigInterface $config)
    {
        // Scalar values should be converted to strings to
        // facilitate differentiation between empty ("") and zero (0).
        if (!$config->getViewTransformers()) {
            return null === $value || is_scalar($value) ? (string) $value : $value;
        }

        foreach ($config->getViewTransformers() as $transformer) {
            $value = $transformer->transform($value);
        }

        return $value;
    }

    /**
     * Reverse transforms a value if a value transformer is set.
     *
     * @param string               $value  The value to reverse transform
     * @param FieldConfigInterface $config
     *
     * @return mixed
     */
    private function viewToNorm($value, FieldConfigInterface $config)
    {
        $transformers = $config->getViewTransformers();

        if (!$transformers) {
            return '' === $value ? null : $value;
        }

        for ($i = count($transformers) - 1; $i >= 0; --$i) {
            $value = $transformers[$i]->reverseTransform($value);
        }

        return $value;
    }
}
