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

use Rollerworks\Component\Search\DataTransformer;

/**
 * Transforms between a number type and a number with grouping
 * (each thousand) and comma separators.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 * @author Florian Eckerstorfer <florian@eckerstorfer.org>
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
abstract class BaseNumberTransformer implements DataTransformer
{
    /**
     * Rounds a number towards positive infinity.
     *
     * Rounds 1.4 to 2 and -1.4 to -1.
     */
    public const ROUND_CEILING = \NumberFormatter::ROUND_CEILING;

    /**
     * Rounds a number towards negative infinity.
     *
     * Rounds 1.4 to 1 and -1.4 to -2.
     */
    public const ROUND_FLOOR = \NumberFormatter::ROUND_FLOOR;

    /**
     * Rounds a number away from zero.
     *
     * Rounds 1.4 to 2 and -1.4 to -2.
     */
    public const ROUND_UP = \NumberFormatter::ROUND_UP;

    /**
     * Rounds a number towards zero.
     *
     * Rounds 1.4 to 1 and -1.4 to -1.
     */
    public const ROUND_DOWN = \NumberFormatter::ROUND_DOWN;

    /**
     * Rounds to the nearest number and halves to the next even number.
     *
     * Rounds 2.5, 1.6 and 1.5 to 2 and 1.4 to 1.
     */
    public const ROUND_HALF_EVEN = \NumberFormatter::ROUND_HALFEVEN;

    /**
     * Rounds to the nearest number and halves away from zero.
     *
     * Rounds 2.5 to 3, 1.6 and 1.5 to 2 and 1.4 to 1.
     */
    public const ROUND_HALF_UP = \NumberFormatter::ROUND_HALFUP;

    /**
     * Rounds to the nearest number and halves towards zero.
     *
     * Rounds 2.5 and 1.6 to 2, 1.5 and 1.4 to 1.
     */
    public const ROUND_HALF_DOWN = \NumberFormatter::ROUND_HALFDOWN;

    /**
     * @var int|null
     */
    protected $scale;

    /**
     * @var bool|null
     */
    protected $grouping;

    /**
     * @var int|null
     */
    protected $roundingMode;

    /**
     * Rounds a number according to the configured scale and rounding mode.
     *
     * @param int|float $number A number
     *
     * @return int|float The rounded number
     */
    protected function round($number)
    {
        if (null !== $this->scale && null !== $this->roundingMode) {
            // shift number to maintain the correct scale during rounding
            $roundingCoef = 10 ** $this->scale;
            $number *= $roundingCoef;

            switch ($this->roundingMode) {
                case self::ROUND_CEILING:
                    $number = ceil($number);
                    break;
                case self::ROUND_FLOOR:
                    $number = floor($number);
                    break;
                case self::ROUND_UP:
                    $number = $number > 0 ? ceil($number) : floor($number);
                    break;
                case self::ROUND_DOWN:
                    $number = $number > 0 ? floor($number) : ceil($number);
                    break;
                case self::ROUND_HALF_EVEN:
                    $number = round($number, 0, PHP_ROUND_HALF_EVEN);
                    break;
                case self::ROUND_HALF_UP:
                    $number = round($number, 0, PHP_ROUND_HALF_UP);
                    break;
                case self::ROUND_HALF_DOWN:
                    $number = round($number, 0, PHP_ROUND_HALF_DOWN);
                    break;
            }

            $number /= $roundingCoef;
        }

        return $number;
    }
}
