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

namespace Rollerworks\Component\Search\Extension\Core\DataTransformer;

use Rollerworks\Component\Search\Exception\TransformationFailedException;

/**
 * Transforms between a number type and a number with rounding.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class NumberToStringTransformer extends BaseNumberTransformer
{
    public function __construct(?int $scale = null, ?int $roundingMode = null)
    {
        $this->scale = $scale;
        $this->roundingMode = $roundingMode ?? self::ROUND_HALF_UP;
    }

    /**
     * Transforms a number type into number.
     *
     * @param int|float|string|null $value Number value
     *
     * @throws TransformationFailedException If the given value is not numeric
     *                                       or if the value can not be transformed
     *
     * @return string
     */
    public function transform($value): string
    {
        if (null !== $value && !is_numeric($value)) {
            throw new TransformationFailedException('Expected a numeric or null.');
        }

        if (null === $value || '' === $value) {
            return '';
        }

        if ($value >= PHP_INT_MAX || $value <= -PHP_INT_MAX) {
            throw new TransformationFailedException('I don\'t have a clear idea what infinity looks like.');
        }

        return (string) $this->round($value);
    }

    /**
     * Transforms a normalized number into an integer or float.
     *
     * @param string $value The localized value
     *
     * @throws TransformationFailedException If the given value is not a string
     *                                       or if the value can not be transformed
     *
     * @return int|float|null The numeric value
     */
    public function reverseTransform($value)
    {
        if (!is_scalar($value)) {
            throw new TransformationFailedException('Expected a scalar.');
        }

        if ('' === $value) {
            return null;
        }

        $result = $value;

        if (!is_numeric($result)) {
            throw new TransformationFailedException('Value is not numeric.');
        }

        if (is_string($result)) {
            if (false !== mb_strpos($result, '.')) {
                $result = (float) $result;
            } else {
                $result = (int) $result;
            }
        }

        if ($result >= PHP_INT_MAX || $result <= -PHP_INT_MAX) {
            throw new TransformationFailedException('I don\'t have a clear idea what infinity looks like.');
        }

        return $this->round($result);
    }
}
