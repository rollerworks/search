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

use Rollerworks\Component\Locale\DateTime as DateTimeHelper;

/**
 * DateTime filter type.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class DateTime extends Date
{
    /**
     * Is the time-part optional.
     *
     * @var boolean
     */
    protected $timeOptional = false;

    /**
     * Constructor.
     *
     * @param boolean $time_optional
     */
    public function __construct($time_optional = false)
    {
        $this->timeOptional = $time_optional;
    }

    /**
     * {@inheritdoc}
     */
    public function sanitizeString($input)
    {
        if (is_object($input)) {
            return $input;
        }

        $hasTime = false;

        if ($input !== $this->lastResult && !DateTimeHelper::validate($input, ($this->timeOptional ? DateTimeHelper::ONLY_DATE_OPTIONAL_TIME : DateTimeHelper::ONLY_DATE_TIME), $this->lastResult, $hasTime) ) {
            throw new \UnexpectedValueException(sprintf('Input value "%s" is not properly validated.', $input));
        }

        $input = $this->lastResult;

        return new DateTimeExtended($input, $hasTime);
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
            \IntlDateFormatter::SHORT,
            ($value->hasSeconds() ? \IntlDateFormatter::LONG : \IntlDateFormatter::SHORT),
            date_default_timezone_get(),
            \IntlDateFormatter::GREGORIAN
        );

        // Make year always four digit
        $pattern = str_replace(array('yy', 'yyyyyyyy'), 'yyyy', $formatter->getPattern());

        // Remove timezone
        if ($value->hasSeconds()) {
            $pattern = preg_replace('/\s*(\(z\)|z)\s*/i', '', $pattern);
        }

        $formatter->setPattern($pattern);

        return $formatter->format($value);
    }

    /**
     * {@inheritdoc}
     *
     * @param \DateTime $input
     */
    public function dumpValue($input)
    {
        return $input->format('Y-m-d\TH:i:s');
    }

    /**
     * {@inheritdoc}
     */
    public function validateValue($input, &$message = null)
    {
        $message = 'This value is not an valid date with ' . ($this->timeOptional ? 'optional ' : '') . 'time';

        return DateTimeHelper::validate($input, ($this->timeOptional ? DateTimeHelper::ONLY_DATE_OPTIONAL_TIME : DateTimeHelper::ONLY_DATE_TIME), $this->lastResult, $this->hasTime);
    }

    /**
     * {@inheritdoc}
     */
    public function getMatcherRegex()
    {
        return DateTimeHelper::getMatcherRegex(($this->timeOptional ? DateTimeHelper::ONLY_DATE_OPTIONAL_TIME : DateTimeHelper::ONLY_DATE_TIME));
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

        if (!$input->hasTime()) {
            $date->modify('+1 day');
        } elseif ($input->hasSeconds()) {
            $date->modify('+1 second');
        } else {
            $date->modify('+1 minute');
        }

        return $date;
    }
}
