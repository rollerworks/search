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

use Rollerworks\RecordFilterBundle\Type\ValueMatcherInterface;

/**
 * Time Formatter-validation type.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class DateTime extends Date
{
    /**
     * Is the time-part optional
     *
     * @var boolean
     */
    protected $timeOptional = false;

    /**
     * @var boolean
     */
    protected $hasTime = false;

    /**
     * Constructor
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

        if ($input !== $this->lastResult && !DateTimeHelper::validateLocalDateTime($input, ($this->timeOptional ? DateTimeHelper::ONLY_DATE_OPTIONAL_TIME : DateTimeHelper::ONLY_DATE_TIME), $this->lastResult, $this->hasTime) ) {
            throw new \UnexpectedValueException(sprintf('Input value "%s" is not properly validated.', $input));
        }

        $input = $this->lastResult;

        return new \DateTime($input);
    }

    /**
     * {@inheritdoc}
     */
    public function formatOutput($value)
    {
        if (!$value instanceof \DateTime) {
            return $value;
        }

        $formatter = \IntlDateFormatter::create(
            \Locale::getDefault(),
            \IntlDateFormatter::SHORT,
            \IntlDateFormatter::SHORT,
            date_default_timezone_get(),
            \IntlDateFormatter::GREGORIAN
        );

        // Make year always four digit
        $formatter->setPattern(str_replace(array('yy', 'yyyyyyyy'), 'yyyy', $formatter->getPattern()));

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

        return DateTimeHelper::validateLocalDateTime($input, ($this->timeOptional ? DateTimeHelper::ONLY_DATE_OPTIONAL_TIME : DateTimeHelper::ONLY_DATE_TIME), $this->lastResult, $this->hasTime);
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
     * @param \DateTime $input
     * @return \DateTime
     */
    public function getHigherValue($input)
    {
        $date = clone $input;

        if (!$this->hasTime) {
            $date->modify('+1 day');
        }
        else {
            $date->modify('+1 minute');
        }

        return $date;
    }
}
