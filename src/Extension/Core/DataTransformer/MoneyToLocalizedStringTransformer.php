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
use Rollerworks\Component\Search\Extension\Core\Model\MoneyValue;

/**
 * Transforms between a normalized format and a localized money string.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 * @author Florian Eckerstorfer <florian@eckerstorfer.org>
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class MoneyToLocalizedStringTransformer extends BaseNumberTransformer
{
    /**
     * @var int|null
     */
    private $divisor;

    /**
     * @var string
     */
    private $defaultCurrency;

    /**
     * @param int    $scale
     * @param bool   $grouping
     * @param int    $roundingMode
     * @param int    $divisor
     * @param string $defaultCurrency
     */
    public function __construct(int $scale = null, bool $grouping = null, int $roundingMode = null, int $divisor = null, string $defaultCurrency = null)
    {
        $this->scale = $scale;
        $this->grouping = $grouping ?? false;
        $this->roundingMode = $roundingMode ?? self::ROUND_HALF_UP;
        $this->defaultCurrency = $defaultCurrency;
        $this->divisor = $divisor ?? 1;
    }

    /**
     * Transforms a normalized format into a localized money string.
     *
     * @param MoneyValue $value Normalized number
     *
     * @throws TransformationFailedException If the given value is not numeric or
     *                                       if the value can not be transformed
     *
     * @return string Localized money string
     */
    public function transform($value)
    {
        if (null === $value) {
            return '';
        }

        if (!$value instanceof MoneyValue) {
            throw new TransformationFailedException('Expected a MoneyValue object.');
        }

        $amountValue = $value->value;
        $amountValue /= $this->divisor;

        $formatter = $this->getNumberFormatter();
        $value = $formatter->formatCurrency((float) $amountValue, $value->currency);

        if (intl_is_failure($formatter->getErrorCode())) {
            throw new TransformationFailedException($formatter->getErrorMessage());
        }

        // Convert fixed spaces to normal ones
        $value = str_replace("\xc2\xa0", ' ', $value);

        return $value;
    }

    /**
     * Transforms a localized number into an integer or float.
     *
     * @param string $value The localized value
     *
     * @throws TransformationFailedException if the given value is not a string
     *                                       or if the value can not be transformed
     *
     * @return MoneyValue
     */
    public function reverseTransform($value)
    {
        if (!is_string($value)) {
            throw new TransformationFailedException('Expected a string.');
        }

        if ('' === $value) {
            return null;
        }

        if ('NaN' === $value) {
            throw new TransformationFailedException('"NaN" is not a valid number');
        }

        $value = str_replace(' ', "\xc2\xa0", $value);
        $currency = '';

        if (!preg_match('#\p{Sc}#u', $value)) {
            $currency = false;
        }

        $position = 0;
        $formatter = $this->getNumberFormatter(false === $currency ? \NumberFormatter::DECIMAL : \NumberFormatter::CURRENCY);
        $groupSep = $formatter->getSymbol(\NumberFormatter::GROUPING_SEPARATOR_SYMBOL);
        $decSep = $formatter->getSymbol(\NumberFormatter::DECIMAL_SEPARATOR_SYMBOL);

        if ('.' !== $decSep && (!$this->grouping || '.' !== $groupSep)) {
            $value = str_replace('.', $decSep, $value);
        }

        if (',' !== $decSep && (!$this->grouping || ',' !== $groupSep)) {
            $value = str_replace(',', $decSep, $value);
        }

        if (false !== $currency) {
            $result = $formatter->parseCurrency($value, $currency, $position);
        } else {
            $result = $formatter->parse($value, \NumberFormatter::TYPE_DOUBLE, $position);
        }

        if (intl_is_failure($formatter->getErrorCode())) {
            throw new TransformationFailedException($formatter->getErrorMessage());
        }

        if ($result >= PHP_INT_MAX || $result <= -PHP_INT_MAX) {
            throw new TransformationFailedException('I don\'t have a clear idea what infinity looks like.');
        }

        if (is_int($result) && $result === (int) $float = (float) $result) {
            $result = $float;
        }

        if (false !== $encoding = mb_detect_encoding($value, null, true)) {
            $length = mb_strlen($value, $encoding);
            $remainder = mb_substr($value, $position, $length, $encoding);
        } else {
            $length = strlen($value);
            $remainder = substr($value, $position, $length);
        }

        // After parsing, position holds the index of the character where the
        // parsing stopped
        if ($position < $length) {
            // Check if there are unrecognized characters at the end of the
            // number (excluding whitespace characters)
            $remainder = trim($remainder, " \t\n\r\0\x0b\xc2\xa0");

            if ('' !== $remainder) {
                throw new TransformationFailedException(
                    sprintf('The number contains unrecognized characters: "%s"', $remainder)
                );
            }
        }

        // NumberFormatter::parse() does not round
        $result = $this->round($result);
        $result *= $this->divisor;

        if (false === $currency) {
            $currency = $this->defaultCurrency;
        }

        return new MoneyValue($currency, (string) $result);
    }

    private function getNumberFormatter(int $type = \NumberFormatter::CURRENCY): \NumberFormatter
    {
        $formatter = new \NumberFormatter(\Locale::getDefault(), $type);

        if (null !== $this->scale) {
            $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, $this->scale);
            $formatter->setAttribute(\NumberFormatter::ROUNDING_MODE, $this->roundingMode);
        }

        $formatter->setAttribute(\NumberFormatter::GROUPING_USED, $this->grouping);

        return $formatter;
    }
}
