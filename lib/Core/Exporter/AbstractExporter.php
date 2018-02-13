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

namespace Rollerworks\Component\Search\Exporter;

use Rollerworks\Component\Search\ConditionExporter;
use Rollerworks\Component\Search\Field\FieldConfig;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\Value\ValuesGroup;

/**
 * AbstractExporter provides the shared logic for the condition exporters.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
abstract class AbstractExporter implements ConditionExporter
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

    abstract protected function exportGroup(ValuesGroup $valuesGroup, FieldSet $fieldSet, bool $isRoot = false);

    /**
     * Transforms the model value to a view representation.
     *
     * @internal
     *
     * @param mixed       $value
     * @param FieldConfig $field
     *
     * @return string
     */
    public function modelToView($value, FieldConfig $field): string
    {
        $transformer = $field->getViewTransformer();

        // Scalar values should be converted to strings to
        // facilitate differentiation between empty ("") and zero (0).
        if (null === $value || !$transformer) {
            if (null !== $value && !is_scalar($value)) {
                throw new \RuntimeException(
                    sprintf(
                        'Model value of type %s is not a scalar value or null and not cannot be '.
                        'converted to a string. You must set a viewTransformer for field "%s" with type "%s".',
                        gettype($value),
                        $field->getName(),
                        get_class($field->getType()->getInnerType())
                    )
                );
            }

            return (string) $value;
        }

        return (string) $transformer->transform($value);
    }

    /**
     * Transforms the model value to a normalized version.
     *
     * @internal
     *
     * @param mixed       $value
     * @param FieldConfig $field
     *
     * @return string
     */
    public function modelToNorm($value, FieldConfig $field): string
    {
        $transformer = $field->getNormTransformer() ?? $field->getViewTransformer();

        // Scalar values should be converted to strings to
        // facilitate differentiation between empty ("") and zero (0).
        if (null === $value || !$transformer) {
            if (null !== $value && !is_scalar($value)) {
                throw new \RuntimeException(
                    sprintf(
                        'Model value of type %s is not a scalar value or null and not cannot be '.
                        'converted to a string. You must set a normTransformer for field "%s" with type "%s".',
                        gettype($value),
                        $field->getName(),
                        get_class($field->getType()->getInnerType())
                    )
                );
            }

            return (string) $value;
        }

        return (string) $transformer->transform($value);
    }
}
