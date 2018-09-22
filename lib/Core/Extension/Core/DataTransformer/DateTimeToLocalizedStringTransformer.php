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
     * Constructor.
     *
     * @see BaseDateTimeTransformer::formats for available format options
     *
     * @param string|null $inputTimezone
     * @param string|null $outputTimezone
     * @param int|null    $dateFormat
     * @param int|null    $timeFormat
     * @param int         $calendar       One of the \IntlDateFormatter calendar constants
     * @param string      $pattern        A pattern to pass to \IntlDateFormatter
     *
     * @throws UnexpectedTypeException If a format is not supported or if a timezone is not a string
     */
    public function __construct(string $inputTimezone = null, string $outputTimezone = null, int $dateFormat = null, int $timeFormat = null, int $calendar = \IntlDateFormatter::GREGORIAN, string $pattern = null)
    {
        parent::__construct($inputTimezone, $outputTimezone);

        if (null === $dateFormat) {
            $dateFormat = \IntlDateFormatter::MEDIUM;
        }

        if (null === $timeFormat) {
            $timeFormat = \IntlDateFormatter::SHORT;
        }

        if (!\in_array($dateFormat, self::$formats, true)) {
            throw new UnexpectedTypeException($dateFormat, implode('", "', self::$formats));
        }

        if (!\in_array($timeFormat, self::$formats, true)) {
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
     * @param \DateTimeInterface|null $dateTime A DateTimeInterface object
     *
     * @throws TransformationFailedException if the given value is not a \DateTimeInterface
     *                                       or if the date could not be transformed
     *
     * @return string|null
     */
    public function transform($dateTime): ?string
    {
        if (null === $dateTime) {
            return '';
        }

        if (!$dateTime instanceof \DateTimeInterface) {
            throw new TransformationFailedException('Expected a \DateTimeInterface.');
        }

        $value = $this->getIntlDateFormatter()->format($dateTime->getTimestamp());

        if (intl_get_error_code() !== 0) {
            throw new TransformationFailedException(intl_get_error_message());
        }

        return $value;
    }

    /**
     * Transforms a localized date string/array into a normalized date.
     *
     * @param string|null $value Localized date string
     *
     * @throws TransformationFailedException if the given value is not a string,
     *                                       if the date could not be parsed
     *
     * @return \DateTime|null
     */
    public function reverseTransform($value)
    {
        if (null !== $value && !\is_string($value)) {
            throw new TransformationFailedException('Expected a string or null.');
        }

        if (null === $value || '' === $value) {
            return null;
        }

        // date-only patterns require parsing to be done in UTC, as midnight might not exist in the local timezone due
        // to DST changes
        $dateOnly = $this->isPatternDateOnly();

        $timestamp = $this->getIntlDateFormatter($dateOnly)->parse($value);

        if (intl_get_error_code() !== 0) {
            throw new TransformationFailedException(intl_get_error_message());
        }

        try {
            if ($dateOnly) {
                // we only care about year-month-date, which has been delivered as a timestamp pointing to UTC midnight
                return new \DateTime(gmdate('Y-m-d', $timestamp), new \DateTimeZone($this->inputTimezone));
            }

            // read timestamp into DateTime object - the formatter delivers a timestamp
            $dateTime = new \DateTime(sprintf('@%s', $timestamp));
            // set timezone separately, as it would be ignored if set via the constructor,
            // see http://php.net/manual/en/datetime.construct.php
            $dateTime->setTimezone(new \DateTimeZone($this->outputTimezone));
        } catch (\Exception $e) {
            throw new TransformationFailedException($e->getMessage(), $e->getCode(), $e);
        }

        if ($this->outputTimezone !== $this->inputTimezone) {
            $dateTime->setTimezone(new \DateTimeZone($this->inputTimezone));
        }

        return $dateTime;
    }

    /**
     * Returns a pre-configured IntlDateFormatter instance.
     *
     * @param bool $ignoreTimezone use UTC regardless of the configured timezone
     *
     * @throws TransformationFailedException in case the date formatter can not be constructed
     *
     * @return \IntlDateFormatter
     */
    protected function getIntlDateFormatter(bool $ignoreTimezone = false): \IntlDateFormatter
    {
        $dateFormat = $this->dateFormat;
        $timeFormat = $this->timeFormat;
        $timezone = $ignoreTimezone ? 'UTC' : $this->outputTimezone;
        $calendar = $this->calendar;
        $pattern = $this->pattern;

        $intlDateFormatter = new \IntlDateFormatter(\Locale::getDefault(), $dateFormat, $timeFormat, $timezone, $calendar);

        if ($pattern) {
            $intlDateFormatter->setPattern($pattern);
        }

        // new \intlDateFormatter may return null instead of false in case of failure, see https://bugs.php.net/bug.php?id=66323
        if (!$intlDateFormatter) {
            throw new TransformationFailedException(intl_get_error_message(), intl_get_error_code());
        }

        $intlDateFormatter->setLenient(false);

        return $intlDateFormatter;
    }

    protected function isPatternDateOnly(): bool
    {
        if (null === $this->pattern) {
            return false;
        }

        // strip escaped text
        $pattern = preg_replace("#'(.*?)'#", '', $this->pattern);

        // check for the absence of time-related placeholders
        return 0 === preg_match('#[ahHkKmsSAzZOvVxX]#', $pattern);
    }
}
