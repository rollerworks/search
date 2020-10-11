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
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
final class DateTimeToRfc3339Transformer extends BaseDateTimeTransformer
{
    /**
     * Transforms a normalized date into a localized date.
     *
     * @param \DateTimeImmutable|null $dateTime
     *
     * @throws TransformationFailedException If the given value is not a \DateTimeImmutable
     *
     * @return string The formatted date
     */
    public function transform($dateTime): string
    {
        if ($dateTime === null) {
            return '';
        }

        if (! $dateTime instanceof \DateTimeImmutable) {
            throw new TransformationFailedException('Expected a \DateTimeImmutable.');
        }

        if ($this->inputTimezone !== $this->outputTimezone) {
            $dateTime = $dateTime->setTimezone(new \DateTimeZone($this->outputTimezone));
        }

        return \preg_replace('/\+00:00$/', 'Z', $dateTime->format('c'));
    }

    /**
     * Transforms a formatted string following RFC 3339 into a normalized date.
     *
     * @param string $rfc3339 Formatted string
     *
     * @throws TransformationFailedException If the given value is not a string,
     *                                       if the value could not be transformed
     */
    public function reverseTransform($rfc3339): ?\DateTimeImmutable
    {
        if (! \is_string($rfc3339)) {
            throw new TransformationFailedException('Expected a string.');
        }

        if ($rfc3339 === '') {
            return null;
        }

        if (! \preg_match('/^(\d{4})-(\d{2})-(\d{2})T\d{2}:\d{2}(?::\d{2})?(?:\.\d+)?(?:Z|(?:(?:\+|-)\d{2}:\d{2}))$/', $rfc3339, $matches)) {
            throw new TransformationFailedException(\sprintf('The date "%s" is not a valid date.', $rfc3339));
        }

        try {
            $dateTime = new \DateTimeImmutable($rfc3339);
        } catch (\Exception $e) {
            throw new TransformationFailedException($e->getMessage(), $e->getCode(), $e);
        }

        if ($this->inputTimezone !== $dateTime->getTimezone()->getName()) {
            $dateTime = $dateTime->setTimezone(new \DateTimeZone($this->inputTimezone));
        }

        if (! \checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1])) {
            throw new TransformationFailedException(\sprintf('The date "%s-%s-%s" is not a valid date.', $matches[1], $matches[2], $matches[3]));
        }

        return $dateTime;
    }
}
