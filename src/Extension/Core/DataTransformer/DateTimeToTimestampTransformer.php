<?php

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
class DateTimeToTimestampTransformer extends BaseDateTimeTransformer
{
    /**
     * Transforms a DateTime object into a timestamp in the configured timezone.
     *
     * @param \DateTime $value A \DateTime object
     *
     * @throws TransformationFailedException If the given value is not an instance
     *                                       of \DateTime or if the output
     *                                       timezone is not supported
     *
     * @return int A timestamp
     */
    public function transform($value)
    {
        if (null === $value) {
            return;
        }

        if (!$value instanceof \DateTime) {
            throw new TransformationFailedException('Expected a \DateTime.');
        }

        $value = clone $value;
        try {
            $value->setTimezone(new \DateTimeZone($this->outputTimezone));
        } catch (\Exception $e) {
            throw new TransformationFailedException($e->getMessage(), $e->getCode(), $e);
        }

        return (int) $value->format('U');
    }

    /**
     * Transforms a timestamp in the configured timezone into a DateTime object.
     *
     * @param string $value A timestamp
     *
     * @throws TransformationFailedException If the given value is not a timestamp
     *                                       or if the given timestamp is invalid
     *
     * @return \DateTime A \DateTime object
     */
    public function reverseTransform($value)
    {
        if (null === $value) {
            return;
        }

        if (!is_numeric($value)) {
            throw new TransformationFailedException('Expected a numeric.');
        }

        try {
            $dateTime = new \DateTime();
            $dateTime->setTimezone(new \DateTimeZone($this->outputTimezone));
            $dateTime->setTimestamp($value);

            if ($this->inputTimezone !== $this->outputTimezone) {
                $dateTime->setTimezone(new \DateTimeZone($this->inputTimezone));
            }
        } catch (\Exception $e) {
            throw new TransformationFailedException($e->getMessage(), $e->getCode(), $e);
        }

        return $dateTime;
    }
}
