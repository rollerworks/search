<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\RecordFilterBundle\Type;

use Rollerworks\RecordFilterBundle\MessageBag;
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
    public function sanitizeString($input)
    {
        if (is_object($input)) {
            return $input;
        }

        if ($input !== $this->lastResult && !DateTimeHelper::validate($input, DateTimeHelper::ONLY_TIME, $this->lastResult) ) {
            throw new \UnexpectedValueException(sprintf('Input value "%s" is not properly validated.', $input));
        }

        $input = $this->lastResult;

        return new DateTimeExtended($input, true);
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
     * @param DateTimeExtended $input
     */
    public function dumpValue($input)
    {
        return $input->format('H:i:s');
    }

    /**
     * {@inheritdoc}
     */
    public function validateValue($input, &$message = null, MessageBag $messageBag = null)
    {
        $message = 'This value is not an valid time';

        if (DateTimeHelper::validateIso($input, DateTimeHelper::ONLY_TIME)) {
            $this->lastResult = $input;
        } elseif (!DateTimeHelper::validate($input, DateTimeHelper::ONLY_TIME, $this->lastResult)) {
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
     * @param DateTimeExtended $input
     * @param DateTimeExtended $nextValue
     */
    public function isHigher($input, $nextValue)
    {
        $firstHour  = (integer) $input->format('H');
        $secondHour = (integer) $nextValue->format('H');

        if ($firstHour <> $secondHour && (0 == $firstHour || 0 == $secondHour)) {
            return true;
        }

        return ($input->getTimestamp() > $nextValue->getTimestamp());
    }

    /**
     * {@inheritdoc}
     *
     * @param DateTimeExtended $input
     * @param DateTimeExtended $nextValue
     */
    public function isLower($input, $nextValue)
    {
        $firstHour  = (integer) $input->format('H');
        $secondHour = (integer) $nextValue->format('H');

        if ($firstHour <> $secondHour && (0 == $firstHour || 0 == $secondHour)) {
            return true;
        }

        return ($input->getTimestamp() < $nextValue->getTimestamp());
    }

    /**
     * {@inheritdoc}
     *
     * @param DateTimeExtended $input
     *
     * @return DateTimeExtended
     */
    public function getHigherValue($input)
    {
        $date = clone $input;

        if ($input->hasSeconds()) {
            $date->modify('+1 second');
        } else {
            $date->modify('+1 minute');
        }

        return $date;
    }
}
