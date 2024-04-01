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
 * Transforms between a timestamp and a DateTime object.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 * @author Florian Eckerstorfer <florian@eckerstorfer.org>
 */
final class DateTimeToTimestampTransformer extends BaseDateTimeTransformer
{
    /**
     * Transforms a DateTime object into a timestamp in the configured timezone.
     *
     * @param \DateTimeImmutable|null $dateTime
     *
     * @return int|null A timestamp
     *
     * @throws TransformationFailedException If the given value is not a \DateTimeImmutable
     */
    public function transform($dateTime): ?int
    {
        if ($dateTime === null) {
            return null;
        }

        if (! $dateTime instanceof \DateTimeImmutable) {
            throw new TransformationFailedException('Expected a \DateTimeImmutable.');
        }

        return $dateTime->getTimestamp();
    }

    /**
     * Transforms a timestamp in the configured timezone into a DateTime object.
     *
     * @param int|string|null $value A timestamp
     *
     * @throws TransformationFailedException If the given value is not a timestamp
     *                                       or if the given timestamp is invalid
     */
    public function reverseTransform($value): ?\DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }

        if (! is_numeric($value)) {
            throw new TransformationFailedException('Expected a numeric.');
        }

        try {
            $dateTime = new \DateTimeImmutable();
            $dateTime = $dateTime->setTimezone(new \DateTimeZone($this->outputTimezone));
            $dateTime = $dateTime->setTimestamp((int) $value);

            if ($this->inputTimezone !== $this->outputTimezone) {
                $dateTime = $dateTime->setTimezone(new \DateTimeZone($this->inputTimezone));
            }
        } catch (\Exception $e) {
            throw new TransformationFailedException($e->getMessage(), $e->getCode(), $e);
        }

        return $dateTime;
    }
}
