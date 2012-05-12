<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\RecordFilterBundle\Type;

use Rollerworks\RecordFilterBundle\Type\FilterTypeInterface;
use Rollerworks\RecordFilterBundle\Formatter\ValuesToRangeInterface;
use Rollerworks\RecordFilterBundle\Value\SingleValue;

use NumberFormatter;

/**
 * Integer Formatter-validation type
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class Number implements FilterTypeInterface, ValuesToRangeInterface
{
    /**
     * @var NumberFormatter|null
     */
    private static $numberFormatter = null;

    /**
     * {@inheritdoc}
     */
    public function sanitizeString($input)
    {
        // Note we explicitly don't cast the value to an integer type
        // 64bit integers are not properly handled on a 32bit OS

        if (ctype_digit((string) ltrim($input, '-+'))) {
            return ltrim($input, '+');
        }

        return self::getNumberFormatter(\Locale::getDefault())->parse(ltrim($input, '+'), NumberFormatter::TYPE_INT64);
    }

    /**
     * {@inheritdoc}
     */
    public function formatOutput($value)
    {
        return self::getNumberFormatter(\Locale::getDefault())->format($value);
    }

    /**
     * {@inheritdoc}
     */
    public function dumpValue($input)
    {
        return $input;
    }

    /**
     * {@inheritdoc}
     */
    public function isHigher($input, $nextValue)
    {
        $phpMax = strlen(PHP_INT_MAX) - 1;

        if ((strlen($input) > $phpMax || strlen($nextValue) > $phpMax) && function_exists('bccomp')) {
            return bccomp($input, $nextValue) === 1;
        }

        return ((integer) $input > (integer) $nextValue);
    }

    /**
     * {@inheritdoc}
     */
    public function isLower($input, $nextValue)
    {
        $phpMax = strlen(PHP_INT_MAX) - 1;

        if ((strlen($input) > $phpMax || strlen($nextValue) > $phpMax) && function_exists('bccomp')) {
            return bccomp($input, $nextValue) === -1;
        }

        return ((integer) $input < (integer) $nextValue);
    }

    /**
     * {@inheritdoc}
     */
    public function isEquals($input, $nextValue)
    {
        return ((string) $input === (string) $nextValue);
    }

    /**
     * {@inheritdoc}
     */
    public function validateValue($input, &$message = null)
    {
        $message = 'This value is no valid number';

        if (!preg_match('/^[+-]?(\p{N}+)$/us', (string) $input)) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function sortValuesList(SingleValue $first, SingleValue $second)
    {
        $phpMax = strlen(PHP_INT_MAX) - 1;

        if ((strlen($first->getValue()) > $phpMax || strlen($second->getValue()) > $phpMax) && function_exists('bccomp')) {
            return bccomp($first->getValue(), $second->getValue());
        }

        if ((integer) $first->getValue() === (integer) $second->getValue()) {
            return 0;
        }

        return ((integer) $first->getValue() < (integer) $second->getValue() ? -1 : 1);
    }

    /**
     * {@inheritdoc}
     */
    public function getHigherValue($input)
    {
        $phpMax = strlen(PHP_INT_MAX) - 1;

        if (strlen($input) > $phpMax && function_exists('bcadd')) {
            return bcadd(ltrim($input, '+'), '1');
        }

        return (intval($input) + 1);
    }

    /**
     * Returns a shared NumberFormatter object.
     *
     * @param null|string $locale
     * @return null|NumberFormatter
     */
    protected static function getNumberFormatter($locale = null)
    {
        $locale = $locale ?: \Locale::getDefault();

        if (null === self::$numberFormatter || self::$numberFormatter->getLocale() !== $locale) {
            self::$numberFormatter = new NumberFormatter($locale, NumberFormatter::PATTERN_DECIMAL);
        }

        return self::$numberFormatter;
    }
}
