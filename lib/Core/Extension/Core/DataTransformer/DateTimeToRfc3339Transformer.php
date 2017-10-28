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
     * {@inheritdoc}
     */
    public function transform($dateTime): ?string
    {
        if (null === $dateTime) {
            return '';
        }

        if (!$dateTime instanceof \DateTimeInterface) {
            throw new TransformationFailedException('Expected a \DateTimeInterface.');
        }

        if ($this->inputTimezone !== $this->outputTimezone) {
            if (!$dateTime instanceof \DateTimeImmutable) {
                $dateTime = clone $dateTime;
            }

            $dateTime = $dateTime->setTimezone(new \DateTimeZone($this->outputTimezone));
        }

        return preg_replace('/\+00:00$/', 'Z', $dateTime->format('c'));
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($rfc3339): ?\DateTime
    {
        if (null !== $rfc3339 && !is_string($rfc3339)) {
            throw new TransformationFailedException('Expected a string or null.');
        }

        if (null === $rfc3339 || '' === $rfc3339) {
            return null;
        }

        try {
            $dateTime = new \DateTime($rfc3339);
        } catch (\Exception $e) {
            throw new TransformationFailedException($e->getMessage(), $e->getCode(), $e);
        }

        if ($this->outputTimezone !== $this->inputTimezone) {
            try {
                $dateTime->setTimezone(new \DateTimeZone($this->inputTimezone));
            } catch (\Exception $e) {
                throw new TransformationFailedException($e->getMessage(), $e->getCode(), $e);
            }
        }

        if (preg_match('/(\d{4})-(\d{2})-(\d{2})/', $rfc3339, $matches) &&
            !checkdate((int) $matches[2], (int) $matches[3], (int) $matches[1])
        ) {
            throw new TransformationFailedException(sprintf(
                'The date "%s-%s-%s" is not a valid date.',
                $matches[1],
                $matches[2],
                $matches[3]
            ));
        }

        return $dateTime;
    }
}
