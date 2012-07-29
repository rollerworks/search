<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Type;

use Rollerworks\Bundle\RecordFilterBundle\MessageBag;
use Rollerworks\Component\Locale\DateTime as DateTimeHelper;

/**
 * Time filter type.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class Time extends Date
{
    /**
     * {@inheritdoc}
     */
    public function sanitizeString($value)
    {
        if (is_object($value)) {
            return $value;
        }

        if ($value !== $this->lastResult && !DateTimeHelper::validate($value, DateTimeHelper::ONLY_TIME, $this->lastResult) ) {
            throw new \UnexpectedValueException(sprintf('Input value "%s" is not properly validated.', $value));
        }

        $value = $this->lastResult;

        return new DateTimeExtended($value, true);
    }

    /**
     * {@inheritdoc}
     *
     * @param DateTimeExtended $value
     */
    public function formatOutput($value)
    {
        if (!$value instanceof DateTimeExtended) {
            return $value;
        }

        $formatter = \IntlDateFormatter::create(
            \Locale::getDefault(),
            \IntlDateFormatter::NONE,
            ($value->hasSeconds() ? \IntlDateFormatter::LONG : \IntlDateFormatter::SHORT),
            date_default_timezone_get(),
            \IntlDateFormatter::GREGORIAN
        );

        // Remove timezone
        if ($value->hasSeconds()) {
            $formatter->setPattern(preg_replace('/\s*(\(z\)|z)\s*/i', '', $formatter->getPattern()));
        }

        return $formatter->format($value);
    }

    /**
     * {@inheritdoc}
     *
     * @param DateTimeExtended $value
     */
    public function dumpValue($value)
    {
        return $value->format('H:i:s');
    }

    /**
     * {@inheritdoc}
     */
    public function validateValue($value, &$message = null, MessageBag $messageBag = null)
    {
        $message = 'This value is not an valid time.';

        if (DateTimeHelper::validateIso($value, DateTimeHelper::ONLY_TIME)) {
            $this->lastResult = $value;
        } elseif (!DateTimeHelper::validate($value, DateTimeHelper::ONLY_TIME, $this->lastResult)) {
            return false;
        }

        if (!$this->validateHigherLower($this->lastResult, $messageBag)) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @param DateTimeExtended $value
     * @param DateTimeExtended $nextValue
     */
    public function isHigher($value, $nextValue)
    {
        $firstHour  = (integer) $value->format('H');
        $secondHour = (integer) $nextValue->format('H');

        if ($firstHour <> $secondHour && (0 == $firstHour || 0 == $secondHour)) {
            return true;
        }

        return ($value->getTimestamp() > $nextValue->getTimestamp());
    }

    /**
     * {@inheritdoc}
     *
     * @param DateTimeExtended $value
     * @param DateTimeExtended $nextValue
     */
    public function isLower($value, $nextValue)
    {
        $firstHour  = (integer) $value->format('H');
        $secondHour = (integer) $nextValue->format('H');

        if ($firstHour <> $secondHour && (0 == $firstHour || 0 == $secondHour)) {
            return true;
        }

        return ($value->getTimestamp() < $nextValue->getTimestamp());
    }

    /**
     * {@inheritdoc}
     *
     * @param DateTimeExtended $value
     *
     * @return DateTimeExtended
     */
    public function getHigherValue($value)
    {
        $date = clone $value;

        if ($value->hasSeconds()) {
            $date->modify('+1 second');
        } else {
            $date->modify('+1 minute');
        }

        return $date;
    }
}
