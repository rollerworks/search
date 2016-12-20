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
 * @author Bernhard Schussek <bschussek@gmail.com>
 * @author Florian Eckerstorfer <florian@eckerstorfer.org>
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class NumberToStringTransformer extends BaseNumberTransformer
{
    /**
     * @param int $scale
     * @param int $roundingMode
     * @param int $type
     */
    public function __construct(int $scale = null, int $roundingMode = null, int $type = \NumberFormatter::DECIMAL)
    {
        $this->scale = $scale;
        $this->roundingMode = $roundingMode ?? self::ROUND_HALF_UP;
        $this->type = $type;
    }

    /**
     * Transforms a number type into number.
     *
     * @param int|float $value Number value
     *
     * @throws TransformationFailedException If the given value is not numeric
     *                                       or if the value can not be transformed
     *
     * @return string
     */
    public function transform($value)
    {
        if (null === $value) {
            return '';
        }

        if (!is_numeric($value)) {
            throw new TransformationFailedException('Expected a numeric.');
        }

        if ($value >= PHP_INT_MAX || $value <= -PHP_INT_MAX) {
            throw new TransformationFailedException('I don\'t have a clear idea what infinity looks like.');
        }

        return (string) $this->round($value);
    }

    /**
     * Transforms a normalized number into an integer or float.
     *
     * @param string $value    The localized value
     * @param string $currency The parsed currency value
     *
     * @throws TransformationFailedException If the given value is not a string
     *                                       or if the value can not be transformed
     *
     * @return int|float|null The numeric value
     */
    public function reverseTransform($value, &$currency = null)
    {
        if (!is_scalar($value)) {
            throw new TransformationFailedException('Expected a scalar.');
        }

        if ('' === $value) {
            return null;
        }

        $currency = false;
        $result = $value;

        if (\NumberFormatter::TYPE_CURRENCY === $this->type && false !== strpos($value, ' ')) {
            list($currency, $result) = explode(' ', $value, 2);

            if (strlen($currency) !== 3) {
                throw new TransformationFailedException(
                    sprintf('Value does not contain a valid 3 character currency code, got "%s".', $currency)
                );
            }
        }

        if (!is_numeric($result)) {
            throw new TransformationFailedException('Value is not numeric.');
        }

        if (is_string($result)) {
            if (false !== strpos($result, '.')) {
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
