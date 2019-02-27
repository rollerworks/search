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
 * @author Franz Wilding <franz.wilding@me.com>
 * @author Bernhard Schussek <bschussek@gmail.com>
 * @author Fred Cox <mcfedr@gmail.com>
 */
final class DateTimeToHtml5LocalDateTimeTransformer extends BaseDateTimeTransformer
{
    public const HTML5_FORMAT = 'Y-m-d\\TH:i:s';

    /**
     * Transforms a \DateTime into a local date and time string.
     *
     * According to the HTML standard, the input string of a datetime-local
     * input is a RFC3339 date followed by 'T', followed by a RFC3339 time.
     * https://html.spec.whatwg.org/multipage/common-microsyntaxes.html#valid-local-date-and-time-string
     *
     * @param \DateTimeInterface|null $dateTime
     *
     * @throws TransformationFailedException If the given value is not an
     *                                       instance of \DateTime or \DateTimeInterface
     *
     * @return string The formatted date
     */
    public function transform($dateTime): string
    {
        if (null === $dateTime) {
            return '';
        }

        if (!$dateTime instanceof \DateTimeInterface) {
            throw new TransformationFailedException('Expected a \DateTime or \DateTimeInterface.');
        }

        if ($this->inputTimezone !== $this->outputTimezone) {
            if (!$dateTime instanceof \DateTimeImmutable) {
                $dateTime = clone $dateTime;
            }

            $dateTime = $dateTime->setTimezone(new \DateTimeZone($this->outputTimezone));
        }

        return $dateTime->format(self::HTML5_FORMAT);
    }

    /**
     * Transforms a local date and time string into a \DateTime.
     *
     * When transforming back to DateTime the regex is slightly laxer, taking into
     * account rules for parsing a local date and time string
     * https://html.spec.whatwg.org/multipage/common-microsyntaxes.html#parse-a-local-date-and-time-string
     *
     * @param string $dateTimeLocal Formatted string
     *
     * @throws TransformationFailedException If the given value is not a string,
     *                                       if the value could not be transformed
     */
    public function reverseTransform($dateTimeLocal): ?\DateTimeInterface
    {
        if (!\is_string($dateTimeLocal)) {
            throw new TransformationFailedException('Expected a string.');
        }

        if ('' === $dateTimeLocal) {
            return null;
        }

        // to maintain backwards compatibility we do not strictly validate the submitted date
        // see https://github.com/symfony/symfony/issues/28699
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})[T ]\d{2}:\d{2}(?::\d{2})?/', $dateTimeLocal, $matches)) {
            throw new TransformationFailedException(sprintf('The date "%s" is not a valid date.', $dateTimeLocal));
        }

        try {
            $dateTime = new \DateTimeImmutable($dateTimeLocal, new \DateTimeZone($this->outputTimezone));
        } catch (\Exception $e) {
            throw new TransformationFailedException($e->getMessage(), $e->getCode(), $e);
        }

        if ($this->inputTimezone !== $dateTime->getTimezone()->getName()) {
            $dateTime = $dateTime->setTimezone(new \DateTimeZone($this->inputTimezone));
        }

        if (!checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1])) {
            throw new TransformationFailedException(sprintf('The date "%s-%s-%s" is not a valid date.', $matches[1], $matches[2], $matches[3]));
        }

        return $dateTime;
    }
}
