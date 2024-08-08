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
use Rollerworks\Component\Search\Exception\UnexpectedTypeException;

/**
 * Transforms between a normalized time and a localized time string.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 * @author Florian Eckerstorfer <florian@eckerstorfer.org>
 */
final class DateTimeToLocalizedStringTransformer extends BaseDateTimeTransformer
{
    private $dateFormat;
    private $timeFormat;
    private $pattern;
    private $calendar;

    /**
     * @see BaseDateTimeTransformer::formats for available format options
     *
     * @param int    $calendar One of the \IntlDateFormatter calendar constants
     * @param string $pattern  A pattern to pass to \IntlDateFormatter
     *
     * @throws UnexpectedTypeException If a format is not supported or if a timezone is not a string
     */
    public function __construct(?string $inputTimezone = null, ?string $outputTimezone = null, ?int $dateFormat = null, ?int $timeFormat = null, int $calendar = \IntlDateFormatter::GREGORIAN, ?string $pattern = null)
    {
        parent::__construct($inputTimezone, $outputTimezone);

        if ($dateFormat === null) {
            $dateFormat = \IntlDateFormatter::MEDIUM;
        }

        if ($timeFormat === null) {
            $timeFormat = \IntlDateFormatter::SHORT;
        }

        if (! \in_array($dateFormat, self::$formats, true)) {
            throw new UnexpectedTypeException($dateFormat, implode('", "', self::$formats));
        }

        if (! \in_array($timeFormat, self::$formats, true)) {
            throw new UnexpectedTypeException($timeFormat, implode('", "', self::$formats));
        }

        $this->dateFormat = $dateFormat;
        $this->timeFormat = $timeFormat;
        $this->calendar = $calendar;
        $this->pattern = $pattern;
    }

    /**
     * Transforms a normalized date into a localized date string/array.
     *
     * @param \DateTimeImmutable|null $dateTime
     *
     * @return string Localized date string
     *
     * @throws TransformationFailedException if the given value is not a \DateTimeImmutable
     *                                       or if the date could not be transformed
     */
    public function transform($dateTime): string
    {
        if ($dateTime === null) {
            return '';
        }

        if (! $dateTime instanceof \DateTimeImmutable) {
            throw new TransformationFailedException('Expected a \DateTimeImmutable.');
        }

        $value = $this->getIntlDateFormatter()->format($dateTime->getTimestamp());

        if (intl_get_error_code() != 0) {
            throw new TransformationFailedException(intl_get_error_message());
        }

        // Convert non-breaking and narrow non-breaking spaces to normal ones
        return str_replace(["\xc2\xa0", "\xe2\x80\xaf"], ' ', $value);
    }

    /**
     * Transforms a localized date string/array into a normalized date.
     *
     * @param string $value Localized date string
     *
     * @throws TransformationFailedException if the given value is not a string,
     *                                       if the date could not be parsed
     */
    public function reverseTransform($value): ?\DateTimeImmutable
    {
        if (! \is_string($value)) {
            throw new TransformationFailedException('Expected a string.');
        }

        if ($value === '') {
            return null;
        }

        // Non-breaking lines are required instead of spaces.
        $value = str_replace(' ', "\xe2\x80\xaf", $value);

        // date-only patterns require parsing to be done in UTC, as midnight might not exist in the local timezone due
        // to DST changes
        $dateOnly = $this->isPatternDateOnly();

        $timestamp = $this->getIntlDateFormatter($dateOnly)->parse($value);

        if (intl_get_error_code() !== 0 || $timestamp === false) {
            throw new TransformationFailedException(intl_get_error_message());
        }

        if ($timestamp > 253402214400) {
            // This timestamp represents UTC midnight of 9999-12-31 to prevent 5+ digit years
            throw new TransformationFailedException('Years beyond 9999 are not supported.');
        }

        try {
            if ($dateOnly) {
                // we only care about year-month-date, which has been delivered as a timestamp pointing to UTC midnight
                return new \DateTimeImmutable(gmdate('Y-m-d', $timestamp), new \DateTimeZone($this->inputTimezone));
            }

            // read timestamp into DateTime object - the formatter delivers a timestamp
            $dateTime = new \DateTimeImmutable(sprintf('@%s', $timestamp));
            // set timezone separately, as it would be ignored if set via the constructor,
            // see http://php.net/manual/en/datetime.construct.php
            $dateTime = $dateTime->setTimezone(new \DateTimeZone($this->outputTimezone));
        } catch (\Exception $e) {
            throw new TransformationFailedException($e->getMessage(), $e->getCode(), $e);
        }

        if ($this->outputTimezone !== $this->inputTimezone) {
            $dateTime = $dateTime->setTimezone(new \DateTimeZone($this->inputTimezone));
        }

        return $dateTime;
    }

    /**
     * Returns a preconfigured IntlDateFormatter instance.
     *
     * @param bool $ignoreTimezone use UTC regardless of the configured timezone
     *
     * @throws TransformationFailedException in case the date formatter can not be constructed
     */
    protected function getIntlDateFormatter(bool $ignoreTimezone = false): \IntlDateFormatter
    {
        $dateFormat = $this->dateFormat;
        $timeFormat = $this->timeFormat;
        $timezone = $ignoreTimezone ? 'UTC' : $this->outputTimezone;

        if (class_exists('IntlTimeZone', false)) {
            // see https://bugs.php.net/bug.php?id=66323
            $timezone = \IntlTimeZone::createTimeZone($timezone);
        }
        $calendar = $this->calendar;
        $pattern = $this->pattern;

        $intlDateFormatter = new \IntlDateFormatter(\Locale::getDefault(), $dateFormat, $timeFormat, $timezone, $calendar);

        // new \IntlDateFormatter may return null instead of false in case of failure, see https://bugs.php.net/bug.php?id=66323
        if (! $intlDateFormatter) {
            throw new TransformationFailedException(intl_get_error_message(), intl_get_error_code());
        }

        if ($pattern) {
            $intlDateFormatter->setPattern($pattern);
        }

        $intlDateFormatter->setLenient(false);

        return $intlDateFormatter;
    }

    protected function isPatternDateOnly(): bool
    {
        if ($this->pattern === null) {
            return false;
        }

        // strip escaped text
        $pattern = preg_replace("#'(.*?)'#", '', $this->pattern);

        // check for the absence of time-related placeholders
        return preg_match('#[ahHkKmsSAzZOvVxX]#', $pattern) === 0;
    }
}
