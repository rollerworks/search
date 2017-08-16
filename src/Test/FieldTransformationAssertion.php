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

namespace Rollerworks\Component\Search\Test;

use PHPUnit\Framework\Assert;
use Rollerworks\Component\Search\Exception\TransformationFailedException;
use Rollerworks\Component\Search\Field\FieldConfig;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class FieldTransformationAssertion
{
    private $field;
    private $inputView;
    private $inputNorm;
    private $transformed = false;
    private $model;

    private function __construct(FieldConfig $field)
    {
        $this->field = $field;
    }

    public static function assertThat(FieldConfig $field): self
    {
        return new self($field);
    }

    public function withInput($inputView, $inputNorm = null): self
    {
        if ($this->transformed) {
            throw new \LogicException('Cannot change input after transformation.');
        }

        $this->inputView = (string) $inputView;
        $this->inputNorm = null === $inputNorm ? $this->inputView : (string) $inputNorm;

        return $this;
    }

    public function successfullyTransformsTo($model): self
    {
        $normValue = $viewValue = null;

        if (null === $this->inputView) {
            throw new \LogicException('withInput() must be called first.');
        }

        try {
            $viewValue = $this->viewToModel($this->inputView);
        } catch (TransformationFailedException $e) {
            Assert::fail('View->model: '.$e->getMessage()."\n".$e->getTraceAsString());
        }

        try {
            $normValue = $this->normToModel($this->inputNorm);
        } catch (TransformationFailedException $e) {
            Assert::fail('Norm->model: '.$e->getMessage()."\n".$e->getTraceAsString());
        }

        Assert::assertEquals($model, $viewValue, 'View->model value does not equal');
        Assert::assertEquals($model, $normValue, 'Norm->model value does not equal');

        $this->transformed = true;
        $this->model = $model;

        return $this;
    }

    public function failsToTransforms(): void
    {
        if (null === $this->inputView) {
            throw new \LogicException('withInput() must be called first.');
        }

        if ($this->transformed) {
            throw new \LogicException('Only successfullyTransformsTo() or failsToTransforms() can be called.');
        }

        try {
            $this->modelToView($this->inputView);

            Assert::fail(sprintf('Expected view-input "%s" to be invalid', $this->inputView));
        } catch (TransformationFailedException $e) {
            Assert::assertTrue(true); // no-op
        }

        try {
            $this->modelToNorm($this->inputNorm);

            Assert::fail(sprintf('Expected norm-input "%s" to be invalid', $this->inputNorm));
        } catch (TransformationFailedException $e) {
            Assert::assertTrue(true); // no-op
        }
    }

    public function andReverseTransformsTo($expectedView = null, $expectedNorm = null)
    {
        $normValue = $viewValue = null;

        if (!$this->transformed) {
            throw new \LogicException('successfullyTransformsTo() must be called first.');
        }

        try {
            $viewValue = $this->modelToView($this->model);
        } catch (TransformationFailedException $e) {
            Assert::fail('Model->view: '.$e->getMessage()."\n".$e->getTraceAsString());
        }

        try {
            $normValue = $this->modelToNorm($this->model);
        } catch (TransformationFailedException $e) {
            Assert::fail('Model->norm: '.$e->getMessage()."\n".$e->getTraceAsString());
        }

        Assert::assertEquals($expectedView, $viewValue, 'View value does not equal');
        Assert::assertEquals($expectedNorm ?? $expectedView, $normValue, 'Norm value does not equal');
    }

    private function viewToModel($value)
    {
        if (!$transformer = $this->field->getViewTransformer()) {
            return '' === $value ? null : $value;
        }

        return $transformer->reverseTransform($value);
    }

    private function modelToView($value): string
    {
        $transformer = $this->field->getViewTransformer();

        // Scalar values should be converted to strings to
        // facilitate differentiation between empty ("") and zero (0).
        if (null === $value || !$transformer) {
            return (string) $value;
        }

        return (string) $transformer->transform($value);
    }

    private function normToModel($value)
    {
        $transformer = $this->field->getNormTransformer() ?? $this->field->getViewTransformer();

        // Scalar values should be converted to strings to
        // facilitate differentiation between empty ("") and zero (0).
        if (null === $value || !$transformer) {
            return (string) $value;
        }

        return $transformer->reverseTransform($value);
    }

    private function modelToNorm($value): string
    {
        $transformer = $this->field->getNormTransformer() ?? $this->field->getViewTransformer();

        // Scalar values should be converted to strings to
        // facilitate differentiation between empty ("") and zero (0).
        if (null === $value || !$transformer) {
            return (string) $value;
        }

        return (string) $transformer->transform($value);
    }
}
