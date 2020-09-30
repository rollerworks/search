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
 * Transforms between a date string and a DateTime object.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 * @author Florian Eckerstorfer <florian@eckerstorfer.org>
 */
final class DateTimeToStringTransformer extends BaseDateTimeTransformer
{
    /**
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
     * Transforms a \DateTimeImmutable instance to a string.
     *
     * @see \DateTimeImmutable::format() for supported formats
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
     * @param \DateTimeImmutable|null $dateTime
     *
     * @throws TransformationFailedException If the given value is not a \DateTimeImmutable
     *
     * @return string A value as produced by PHP's date() function
     */
    public function transform($dateTime): string
    {
        if (null === $dateTime) {
            return '';
        }

        if (!$dateTime instanceof \DateTimeImmutable) {
            throw new TransformationFailedException('Expected a \DateTimeImmutable.');
        }

        if (!$dateTime instanceof \DateTimeImmutable) {
            $dateTime = clone $dateTime;
        }

        $dateTime = $dateTime->setTimezone(new \DateTimeZone($this->outputTimezone));

        return $dateTime->format($this->generateFormat);
    }

    /**
     * Transforms a date string in the configured timezone into a DateTime object.
     *
     * @param string $value A value as produced by PHP's date() function
     *
     * @throws TransformationFailedException If the given value is not a string,
     *                                       or could not be transformed
     */
    public function reverseTransform($value): ?\DateTimeImmutable
    {
        if (empty($value)) {
            return null;
        }

        if (!\is_string($value)) {
            throw new TransformationFailedException('Expected a string.');
        }

        $outputTz = new \DateTimeZone($this->outputTimezone);
        $dateTime = \DateTimeImmutable::createFromFormat($this->parseFormat, $value, $outputTz);

        $lastErrors = \DateTimeImmutable::getLastErrors();

        if (0 < $lastErrors['warning_count'] || 0 < $lastErrors['error_count']) {
            throw new TransformationFailedException(implode(', ', array_merge(array_values($lastErrors['warnings']), array_values($lastErrors['errors']))));
        }

        try {
            if ($this->inputTimezone !== $this->outputTimezone) {
                $dateTime = $dateTime->setTimezone(new \DateTimeZone($this->inputTimezone));
            }
        } catch (\Exception $e) {
            throw new TransformationFailedException($e->getMessage(), $e->getCode(), $e);
        }

        return $dateTime;
    }
}
