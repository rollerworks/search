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

use Rollerworks\RecordFilterBundle\Formatter\ValuesToRangeInterface;
use Rollerworks\RecordFilterBundle\Value\SingleValue;
use NumberFormatter;

/**
 * Decimal Formatter-validation type
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * @todo Filter extension instead of an Regex and detect proper decimal-sign
 */
class Decimal implements FilterTypeInterface, ValueMatcherInterface, ValuesToRangeInterface
{
    /**
     * @var string
     */
    protected $lastResult;

    /**
     * @var NumberFormatter|null
     */
    static private $numberFormatter = null;

    /**
     * {@inheritdoc}
     */
    public function sanitizeString($input)
    {
        // Note we explicitly don't cast the value to an float type
        // 64bit floats are not properly handled on a 32bit OS

        if (!preg_match('/[^.0-9-]/', $input)) {
            return ltrim($input, '+');
        }

        if ($input !== $this->lastResult && !$this->validateValue($input) ) {
            throw new \UnexpectedValueException(sprintf('Input value "%s" is not properly validated.', $input));
        }

        return $this->lastResult;
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

        return ($input > $nextValue);
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

        return ($input < $nextValue);
    }

    /**
     * {@inheritdoc}
     */
    public function isEquals($input, $nextValue)
    {
        $phpMax = strlen(PHP_INT_MAX) - 1;

        if ((strlen($input) > $phpMax || strlen($nextValue) > $phpMax) && function_exists('gmp_cmp')) {
            return bccomp($input, $nextValue) === 0;
        }

        return ((float) $input == (float) $nextValue);
    }

    /**
     * {@inheritdoc}
     */
    public function validateValue($input, &$message=null)
    {
        $message = 'This value is not an valid decimal';

        $this->lastResult = self::getNumberFormatter(\Locale::getDefault())->parse($input);

        if (!$this->lastResult) {
            return false;
        } else {
            return true;
        }
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

        if ((float) $first->getValue() === (float) $second->getValue()) {
            return 0;
        }

        return ((float) $first->getValue() < (float) $second->getValue()) ? -1 : 1;
    }

    /**
     * {@inheritdoc}
     */
    public function getHigherValue($input)
    {
        $phpMax = strlen(PHP_INT_MAX) - 1;

        if (strlen($input) > $phpMax && function_exists('bcadd')) {
            return bcadd(ltrim($input, '+'), '0.01');
        }

        return ((float) $input) + 0.01;
    }

    /**
     * {@inheritdoc}
     */
    public function getMatcherRegex()
    {
        return '(?:\p{N}+,\p{N}+|\p{N}+.\p{N}+)';
    }

    /**
     * Returns a shared NumberFormatter object.
     *
     * @param null|string $locale
     * @return null|NumberFormatter
     */
    static protected function getNumberFormatter($locale = null)
    {
        $locale = $locale ?: \Locale::getDefault();

        if (null === self::$numberFormatter || self::$numberFormatter->getLocale() !== $locale) {
            self::$numberFormatter = new NumberFormatter($locale, NumberFormatter::DECIMAL);
        }

        return self::$numberFormatter;
    }
}
