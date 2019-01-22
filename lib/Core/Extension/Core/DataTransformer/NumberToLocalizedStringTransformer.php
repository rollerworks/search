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
 * Transforms between a number type and a localized number with grouping
 * (each thousand) and comma separators.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 * @author Florian Eckerstorfer <florian@eckerstorfer.org>
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class NumberToLocalizedStringTransformer extends BaseNumberTransformer
{
    public function __construct(?int $scale = null, ?bool $grouping = null, ?int $roundingMode = null)
    {
        $this->scale = $scale;
        $this->grouping = $grouping ?? false;
        $this->roundingMode = $roundingMode ?? self::ROUND_HALF_UP;
    }

    /**
     * Transforms a number type into localized number.
     *
     * @param int|float|string|null $value Number value
     *
     * @throws TransformationFailedException If the given value is not numeric
     *                                       or if the value can not be transformed
     */
    public function transform($value): string
    {
        if (null !== $value && !is_numeric($value)) {
            throw new TransformationFailedException('Expected a numeric or null.');
        }

        if (null === $value || '' === $value) {
            return '';
        }

        $formatter = $this->getNumberFormatter();
        $value = $formatter->format($value);

        if (intl_is_failure($formatter->getErrorCode())) {
            throw new TransformationFailedException($formatter->getErrorMessage());
        }

        // Convert fixed spaces to normal ones
        $value = str_replace(["\xc2\xa0", "\xe2\x80\xaf"], ' ', $value);

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
     * @return int|float|null The numeric value
     */
    public function reverseTransform($value)
    {
        if (!\is_string($value)) {
            throw new TransformationFailedException('Expected a string.');
        }

        if ('' === $value) {
            return null;
        }

        if ('NaN' === $value) {
            throw new TransformationFailedException('"NaN" is not a valid number');
        }

        $position = 0;
        $formatter = $this->getNumberFormatter();
        $groupSep = $formatter->getSymbol(\NumberFormatter::GROUPING_SEPARATOR_SYMBOL);
        $decSep = $formatter->getSymbol(\NumberFormatter::DECIMAL_SEPARATOR_SYMBOL);

        if ('.' !== $decSep && (!$this->grouping || '.' !== $groupSep)) {
            $value = str_replace('.', $decSep, $value);
        }

        if (',' !== $decSep && (!$this->grouping || ',' !== $groupSep)) {
            $value = str_replace(',', $decSep, $value);
        }

        if (false !== mb_strpos($value, $decSep)) {
            $type = \NumberFormatter::TYPE_DOUBLE;
        } else {
            $type = PHP_INT_SIZE === 8
                ? \NumberFormatter::TYPE_INT64
                : \NumberFormatter::TYPE_INT32;
        }

        /** @var int|float|false $result */
        $result = $formatter->parse($value, $type, $position);

        if (intl_is_failure($formatter->getErrorCode())) {
            throw new TransformationFailedException($formatter->getErrorMessage());
        }

        if ($result >= PHP_INT_MAX || $result <= -PHP_INT_MAX) {
            throw new TransformationFailedException('I don\'t have a clear idea what infinity looks like.');
        }

        if (\is_int($result) && $result === (int) ($float = (float) $result)) {
            $result = $float;
        }

        if (false !== $encoding = mb_detect_encoding($value, null, true)) {
            $length = mb_strlen($value, $encoding);
            $remainder = mb_substr($value, $position, $length, $encoding);
        } else {
            $length = mb_strlen($value);
            $remainder = mb_substr($value, $position, $length);
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
        return $this->round($result);
    }

    private function getNumberFormatter(): \NumberFormatter
    {
        $formatter = new \NumberFormatter(\Locale::getDefault(), \NumberFormatter::DECIMAL);

        if (null !== $this->scale) {
            $formatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, $this->scale);
            $formatter->setAttribute(\NumberFormatter::ROUNDING_MODE, $this->roundingMode);
        }

        $formatter->setAttribute(\NumberFormatter::GROUPING_USED, $this->grouping);

        return $formatter;
    }
}
