<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Test;

use Rollerworks\Component\Search\Exception\TransformationFailedException;
use Rollerworks\Component\Search\FieldConfigInterface;

abstract class FieldTypeTestCase extends SearchIntegrationTestCase
{
    public static function assertDateTimeEquals(\DateTime $expected, \DateTime $actual)
    {
        self::assertEquals(
            $expected->format('c'),
            $actual->format('c')
        );
    }

    protected function assertTransformedEquals(FieldConfigInterface $field, $expectedValue, $input, $expectedView = null)
    {
        $values = $this->formatInput($field, $input);

        if ($expectedValue instanceof \DateTime) {
            $this->assertDateTimeEquals($expectedValue, $values[0]);
        } else {
            $this->assertEquals($expectedValue, $values[0]);
        }

        if (null !== $expectedView) {
            $this->assertEquals($expectedView, $values[1]);
        }
    }

    protected function assertTransformedFails(FieldConfigInterface $field, $input)
    {
        $normValue = $this->viewToNorm($input, $field);
        $this->assertInstanceOf('Rollerworks\Component\Search\Exception\TransformationFailedException', $normValue);
    }

    protected function assertTransformedNotEquals(FieldConfigInterface $field, $expectedValue, $input)
    {
        $value = $this->formatInput($field, $input);

        $this->assertNotEquals($expectedValue, $value[0]);
    }

    protected function formatInput(FieldConfigInterface $field, $input)
    {
        $normValue = $this->viewToNorm($input, $field);
        if ($normValue instanceof TransformationFailedException) {
            $this->fail('Norm: '.$normValue->getMessage());
        }

        $viewValue = $this->normToView($normValue, $field);
        if ($viewValue instanceof TransformationFailedException) {
            $this->fail('Norm: '.$viewValue->getMessage());
        }

        return [$normValue, $viewValue];
    }

    /**
     * Transforms the value if a value transformer is set.
     *
     * @param mixed                $value  The value to transform
     * @param FieldConfigInterface $config
     *
     * @return string|null Returns null when the value is empty or invalid
     */
    protected function normToView($value, FieldConfigInterface $config)
    {
        // Scalar values should be converted to strings to
        // facilitate differentiation between empty ("") and zero (0).
        if (!$config->getViewTransformers()) {
            return null === $value || is_scalar($value) ? (string) $value : $value;
        }

        try {
            foreach ($config->getViewTransformers() as $transformer) {
                $value = $transformer->transform($value);
            }

            return $value;
        } catch (TransformationFailedException $e) {
            return $e;
        }
    }

    /**
     * Reverse transforms a value if a value transformer is set.
     *
     * @param string               $value  The value to reverse transform
     * @param FieldConfigInterface $config
     *
     * @return mixed Returns null when the value is empty or invalid
     */
    protected function viewToNorm($value, FieldConfigInterface $config)
    {
        $transformers = $config->getViewTransformers();

        if (!$transformers) {
            return '' === $value ? null : $value;
        }

        try {
            for ($i = count($transformers) - 1; $i >= 0; --$i) {
                $value = $transformers[$i]->reverseTransform($value);
            }

            return $value;
        } catch (TransformationFailedException $e) {
            return $e;
        }
    }
}
