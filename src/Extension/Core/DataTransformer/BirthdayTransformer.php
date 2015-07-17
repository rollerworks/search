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

use Rollerworks\Component\Search\DataTransformerInterface;
use Rollerworks\Component\Search\Exception\TransformationFailedException;

/**
 * Transforms between a date string and a DateTime object
 * and between a localized string and a integer.
 */
class BirthdayTransformer implements DataTransformerInterface
{
    /**
     * @var DataTransformerInterface[]
     */
    private $transformers;

    /**
     * @var bool
     */
    private $allowAge;

    /**
     * @var bool
     */
    private $allowFutureDate;

    /**
     * @param DataTransformerInterface[] $transformers
     * @param bool                       $allowAge
     * @param bool                       $allowFutureDate
     */
    public function __construct($transformers, $allowAge, $allowFutureDate)
    {
        $this->transformers = $transformers;
        $this->allowFutureDate = $allowFutureDate;
        $this->allowAge = $allowAge;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if (is_int($value)) {
            return $this->getNumberFormatter()->format($value, \NumberFormatter::DECIMAL);
        }

        foreach ($this->transformers as $transformer) {
            $value = $transformer->transform($value);
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        $value = $this->transformWhenInteger($value);

        if (is_int($value)) {
            if (!$this->allowAge) {
                throw new TransformationFailedException('Age is not supported.');
            }

            return $value;
        }

        $transformers = $this->transformers;

        for ($i = count($transformers) - 1; $i >= 0; --$i) {
            $value = $transformers[$i]->reverseTransform($value);
        }

        // Force the UTC timezone with 00:00:00 for correct comparison.
        $value = clone $value;
        $value->setTimezone(new \DateTimeZone('UTC'));
        $value->setTime(0, 0, 0);

        if (!$this->allowFutureDate) {
            $this->validateDate($value);
        }

        return $value;
    }

    private function transformWhenInteger($value)
    {
        if (ctype_digit($value)) {
            return (int) $value;
        }

        if (preg_match('/^\p{N}+$/', $value)) {
            return $this->getNumberFormatter()->parse($value, \NumberFormatter::DECIMAL);
        }

        return $value;
    }

    /**
     * @param \DateTime|\DateTimeInterface $value
     */
    private function validateDate($value)
    {
        static $currentDate;

        if (!$currentDate) {
            $currentDate = new \DateTime('now', new \DateTimeZone('UTC'));
            $currentDate->setTime(0, 0, 0);
        }

        if ($value > $currentDate) {
            throw new TransformationFailedException(
                sprintf(
                    'Date "%s" is higher then current date "%s". Are you a time traveler?',
                    $value->format('Y-m-d'),
                    $currentDate->format('Y-m-d')
                )
            );
        }
    }

    /**
     * Returns a pre-configured \NumberFormatter instance.
     *
     * @return \NumberFormatter
     */
    private function getNumberFormatter()
    {
        /** @var \NumberFormatter $formatter */
        static $formatter;

        if (!$formatter || $formatter->getLocale() !== \Locale::getDefault()) {
            $formatter = new \NumberFormatter(\Locale::getDefault(), \NumberFormatter::DECIMAL);
            $formatter->setAttribute(\NumberFormatter::GROUPING_USED, false);
        }

        return $formatter;
    }
}
