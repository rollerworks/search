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
 * Transforms between a date string and a DateTime object.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 * @author Florian Eckerstorfer <florian@eckerstorfer.org>
 */
final class DateTimeToStringTransformer extends BaseDateTimeTransformer
{
    /**
     * Format used for generating strings.
     *
     * @var string
     */
    private $generateFormat;

    /**
     * Format used for parsing strings.
     *
     * Different than the {@link $generateFormat} because formats for parsing
     * support additional characters in PHP that are not supported for
     * generating strings.
     *
     * @var string
     */
    private $parseFormat;

    /**
     * Transforms a \DateTime instance to a string.
     *
     * @see \DateTime::format() for supported formats
     *
     * @param string|null $inputTimezone
     * @param string|null $outputTimezone
     * @param string      $format
     *
     * @throws UnexpectedTypeException if a timezone is not a string
     */
    public function __construct(string $inputTimezone = null, string $outputTimezone = null, string $format = 'Y-m-d H:i:s')
    {
        parent::__construct($inputTimezone, $outputTimezone);

        $this->generateFormat = $this->parseFormat = $format;

        // See http://php.net/manual/en/datetime.createfromformat.php
        // The character "|" in the format makes sure that the parts of a date
        // that are *not* specified in the format are reset to the corresponding
        // values from 1970-01-01 00:00:00 instead of the current time.
        // Without "|" and "Y-m-d", "2010-02-03" becomes "2010-02-03 12:32:47",
        // where the time corresponds to the current server time.
        // With "|" and "Y-m-d", "2010-02-03" becomes "2010-02-03 00:00:00",
        // which is at least deterministic and thus used here.
        if (false === strpos($this->parseFormat, '|')) {
            $this->parseFormat .= '|';
        }
    }

    /**
     * Transforms a DateTime object into a date string with the configured format
     * and timezone.
     *
     * @param \DateTimeInterface|null $value A DateTime object
     *
     * @throws TransformationFailedException If the given value is not a \DateTime
     *                                       instance or if the output timezone
     *                                       is not supported
     *
     * @return string|null A value as produced by PHP's date() function
     */
    public function transform($value): ?string
    {
        if (null === $value) {
            return '';
        }

        if (!$value instanceof \DateTimeInterface) {
            throw new TransformationFailedException('Expected a \DateTimeInterface.');
        }

        if (!$value instanceof \DateTimeImmutable) {
            $value = clone $value;
        }

        try {
            $value = $value->setTimezone(new \DateTimeZone($this->outputTimezone));
        } catch (\Exception $e) {
            throw new TransformationFailedException($e->getMessage(), $e->getCode(), $e);
        }

        return $value->format($this->generateFormat);
    }

    /**
     * Transforms a date string in the configured timezone into a DateTime object.
     *
     * @param string|null $value A value as produced by PHP's date() function
     *
     * @throws TransformationFailedException If the given value is not a string,
     *                                       if the date could not be parsed or
     *                                       if the input timezone is not supported
     *
     * @return \DateTime|null An instance of \DateTime
     */
    public function reverseTransform($value): ?\DateTime
    {
        if (null !== $value && !\is_string($value)) {
            throw new TransformationFailedException('Expected a string or null.');
        }

        if (null === $value || '' === $value) {
            return null;
        }

        try {
            $outputTz = new \DateTimeZone($this->outputTimezone);
            $dateTime = \DateTime::createFromFormat($this->parseFormat, $value, $outputTz);
            $lastErrors = \DateTime::getLastErrors();

            if (0 < $lastErrors['warning_count'] || 0 < $lastErrors['error_count']) {
                throw new TransformationFailedException(
                    implode(', ', array_merge(
                        array_values($lastErrors['warnings']),
                        array_values($lastErrors['errors'])
                    ))
                );
            }

            if ($this->inputTimezone !== $this->outputTimezone) {
                $dateTime->setTimezone(new \DateTimeZone($this->inputTimezone));
            }
        } catch (TransformationFailedException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new TransformationFailedException($e->getMessage(), $e->getCode(), $e);
        }

        return $dateTime;
    }
}
