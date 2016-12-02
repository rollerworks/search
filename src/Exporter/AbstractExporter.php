<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Exporter;

use Rollerworks\Component\Search\ExporterInterface;
use Rollerworks\Component\Search\FieldConfigInterface;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\ValuesGroup;

/**
 * AbstractExporter provides the shared logic for the condition exporters.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
abstract class AbstractExporter implements ExporterInterface
{
    /**
     * Exports a search condition.
     *
     * @param SearchCondition $condition The search condition to export
     *
     * @return mixed
     */
    public function exportCondition(SearchCondition $condition)
    {
        return $this->exportGroup($condition->getValuesGroup(), $condition->getFieldSet(), true);
    }

    /**
     * @param PatternMatch $patternMatch
     *
     * @throws \RuntimeException When an unsupported pattern-match type is provided
     *
     * @return string
     */
    protected function getPatternMatchType(PatternMatch $patternMatch)
    {
        $type = '';

        if ($patternMatch->isExclusive()) {
            $type .= 'NOT_';
        }

        switch ($patternMatch->getType()) {
            case PatternMatch::PATTERN_CONTAINS:
            case PatternMatch::PATTERN_NOT_CONTAINS:
                $type .= 'CONTAINS';
                break;

            case PatternMatch::PATTERN_STARTS_WITH:
            case PatternMatch::PATTERN_NOT_STARTS_WITH:
                $type .= 'STARTS_WITH';
                break;

            case PatternMatch::PATTERN_ENDS_WITH:
            case PatternMatch::PATTERN_NOT_ENDS_WITH:
                $type .= 'ENDS_WITH';
                break;

            case PatternMatch::PATTERN_REGEX:
            case PatternMatch::PATTERN_NOT_REGEX:
                $type .= 'REGEX';
                break;

            case PatternMatch::PATTERN_EQUALS:
            case PatternMatch::PATTERN_NOT_EQUALS:
                $type .= 'EQUALS';
                break;

            default:
                throw new \RuntimeException(
                    sprintf(
                        'Unsupported pattern-match type "%s" found. Please report this bug.',
                        $patternMatch->getType()
                    )
                );
        }

        return $type;
    }

    /**
     * @param ValuesGroup $valuesGroup
     * @param FieldSet    $fieldSet
     * @param bool        $isRoot
     *
     * @return mixed
     */
    abstract protected function exportGroup(ValuesGroup $valuesGroup, FieldSet $fieldSet, $isRoot = false);

    /**
     * Transforms the value if a value transformer is set.
     *
     * @param mixed                $value
     * @param FieldConfigInterface $field
     *
     * @return string
     */
    protected function normToView($value, FieldConfigInterface $field): string
    {
        // Scalar values should be converted to strings to
        // facilitate differentiation between empty ("") and zero (0).
        if (null === $value || !$field->getViewTransformers()) {
            if (null !== $value && !is_scalar($value)) {
                throw new \RuntimeException(
                    sprintf(
                        'Norm value of type %s is not a scalar value or null and not cannot be '.
                        'converted to a string. You must set a viewTransformer for field "%s" with type "%s".',
                        gettype($value),
                        $field->getName(),
                        $field->getType()->getName()
                    )
                );
            }

            return (string) $value;
        }

        foreach ($field->getViewTransformers() as $transformer) {
            $value = $transformer->transform($value);
        }

        return (string) $value;
    }
}
