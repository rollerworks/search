<?php

/**
 * This file is part of RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Extension\Core\DataTransformer;

use Rollerworks\Component\Search\Exception\TransformationFailedException;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class DateTimeToRfc3339Transformer extends BaseDateTimeTransformer
{
    /**
     * {@inheritDoc}
     */
    public function transform($dateTime)
    {
        if (null === $dateTime) {
            return '';
        }

        if (!$dateTime instanceof \DateTime) {
            throw new TransformationFailedException('Expected a \DateTime.');
        }

        if ($this->inputTimezone !== $this->outputTimezone) {
            $dateTime = clone $dateTime;
            $dateTime->setTimezone(new \DateTimeZone($this->outputTimezone));
        }

        return preg_replace('/\+00:00$/', 'Z', $dateTime->format('c'));
    }

    /**
     * {@inheritDoc}
     */
    public function reverseTransform($rfc3339)
    {
        if (!is_string($rfc3339)) {
            throw new TransformationFailedException('Expected a string.');
        }

        if ('' === $rfc3339) {
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

        if (preg_match('/(\d{4})-(\d{2})-(\d{2})/', $rfc3339, $matches)) {
            if (!checkdate($matches[2], $matches[3], $matches[1])) {
                throw new TransformationFailedException(sprintf(
                    'The date "%s-%s-%s" is not a valid date.',
                    $matches[1],
                    $matches[2],
                    $matches[3]
                ));
            }
        }

        return $dateTime;
    }
}
