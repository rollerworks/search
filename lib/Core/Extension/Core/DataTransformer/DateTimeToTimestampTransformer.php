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
     * Transforms a DateTimeInterface object into a timestamp.
     *
     * @param \DateTimeInterface|null $dateTime
     *
     * @throws TransformationFailedException If the given value is not an instance
     *                                       of \DateTime or if the output
     *                                       timezone is not supported
     *
     * @return int|null A timestamp
     */
    public function transform($dateTime): ?int
    {
        if (null === $dateTime) {
            return null;
        }

        if (!$dateTime instanceof \DateTimeInterface) {
            throw new TransformationFailedException('Expected a \DateTime.');
        }

        return $dateTime->getTimestamp();
    }

    /**
     * Transforms a timestamp in the configured timezone into a DateTime object.
     *
     * @param string|int|null $value A timestamp
     *
     * @throws TransformationFailedException If the given value is not a timestamp
     *                                       or if the given timestamp is invalid
     */
    public function reverseTransform($value): ?\DateTime
    {
        if (null !== $value && !is_numeric($value)) {
            throw new TransformationFailedException('Expected a numeric or null.');
        }

        if (null === $value || '' === $value) {
            return null;
        }

        try {
            $dateTime = new \DateTime();
            $dateTime->setTimezone(new \DateTimeZone($this->outputTimezone));
            $dateTime->setTimestamp((int) $value);

            if ($this->inputTimezone !== $this->outputTimezone) {
                $dateTime->setTimezone(new \DateTimeZone($this->inputTimezone));
            }
        } catch (\Exception $e) {
            throw new TransformationFailedException($e->getMessage(), $e->getCode(), $e);
        }

        return $dateTime;
    }
}
