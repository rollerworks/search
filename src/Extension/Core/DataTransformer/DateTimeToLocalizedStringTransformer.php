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
class DateTimeToLocalizedStringTransformer extends BaseDateTimeTransformer
{
    private $dateFormat;
    private $timeFormat;
    private $pattern;
    private $calendar;

    /**
     * Constructor.
     *
     * @see \IntlDateFormatter for available format options
     *
     * @param string $inputTimezone  The name of the input timezone
     * @param string $outputTimezone The name of the output timezone
     * @param int    $dateFormat     The date format
     * @param int    $timeFormat     The time format
     * @param int    $calendar       One of the \IntlDateFormatter calendar constants
     * @param string $pattern        A pattern to pass to \IntlDateFormatter
     *
     * @throws UnexpectedTypeException If a format is not supported or if a timezone is not a string
     */
    public function __construct(string $inputTimezone = null, string $outputTimezone = null, int $dateFormat = null, int $timeFormat = null, int $calendar = \IntlDateFormatter::GREGORIAN, string $pattern = null)
    {
        parent::__construct($inputTimezone, $outputTimezone);

        $this->dateFormat = $dateFormat ?? \IntlDateFormatter::MEDIUM;
        $this->timeFormat = $timeFormat ?? \IntlDateFormatter::SHORT;
        $this->calendar = $calendar;
        $this->pattern = $pattern;
    }

    /**
     * Transforms a normalized date into a localized date string/array.
     *
     * @param \DateTimeInterface $dateTime
     *
     * @throws TransformationFailedException If the given value is not an instance
     *                                       of \DateTime or if the date could not
     *                                       be transformed
     *
     * @return string Localized date string
     */
    public function transform($dateTime)
    {
        if (null === $dateTime) {
            return '';
        }

        if (!$dateTime instanceof \DateTimeInterface) {
            throw new TransformationFailedException('Expected a \DateTimeInterface.');
        }

        // convert time to UTC before passing it to the formatter
        if (!$dateTime instanceof \DateTimeImmutable) {
            $dateTime = clone $dateTime;
        }

        if ('UTC' !== $this->inputTimezone) {
            $dateTime = $dateTime->setTimezone(new \DateTimeZone('UTC'));
        }

        $value = $this->getIntlDateFormatter()->format(
            (int) $dateTime->format('U')
        );

        if (intl_get_error_code() !== 0) {
            throw new TransformationFailedException(intl_get_error_message());
        }

        return $value;
    }

    /**
     * Transforms a localized date string/array into a normalized date.
     *
     * @param string|array $value Localized date string/array
     *
     * @throws TransformationFailedException if the given value is not a string,
     *                                       if the date could not be parsed or
     *                                       if the input timezone is not supported
     *
     * @return \DateTime|null Normalized date
     */
    public function reverseTransform($value)
    {
        if (!is_string($value)) {
            throw new TransformationFailedException('Expected a string.');
        }

        if ('' === $value) {
            return null;
        }

        $timestamp = $this->getIntlDateFormatter()->parse($value);

        if (intl_get_error_code() !== 0) {
            throw new TransformationFailedException(intl_get_error_message());
        }

        try {
            // read timestamp into DateTime object - the formatter delivers in UTC
            $dateTime = new \DateTime(sprintf('@%s UTC', $timestamp));
        } catch (\Exception $e) {
            throw new TransformationFailedException(
                $e->getMessage(),
                $e->getCode(),
                $e
            );
        }

        if ('UTC' !== $this->inputTimezone) {
            try {
                $dateTime->setTimezone(new \DateTimeZone($this->inputTimezone));
            } catch (\Exception $e) {
                throw new TransformationFailedException(
                    $e->getMessage(),
                    $e->getCode(),
                    $e
                );
            }
        }

        return $dateTime;
    }

    /**
     * Returns a pre-configured IntlDateFormatter instance.
     *
     * @return \IntlDateFormatter
     */
    protected function getIntlDateFormatter(): \IntlDateFormatter
    {
        $intlDateFormatter = new \IntlDateFormatter(
            \Locale::getDefault(),
            $this->dateFormat,
            $this->timeFormat,
            $this->outputTimezone,
            $this->calendar
        );

        if ($this->pattern) {
            $intlDateFormatter->setPattern($this->pattern);
        }

        $intlDateFormatter->setLenient(false);

        return $intlDateFormatter;
    }
}
