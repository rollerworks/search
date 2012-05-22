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
use Rollerworks\RecordFilterBundle\Formatter\ValuesToRangeInterface;
use Rollerworks\RecordFilterBundle\Value\SingleValue;

/**
 * Date filter type.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class Date implements FilterTypeInterface, ValueMatcherInterface, ValuesToRangeInterface
{
    /**
     * @var string
     */
    protected $lastResult;

    /**
     * {@inheritdoc}
     *
     * @return DateTimeExtended
     */
    public function sanitizeString($input)
    {
        if (is_object($input)) {
            return $input;
        }

        if ($input !== $this->lastResult && !DateTimeHelper::validate($input, DateTimeHelper::ONLY_DATE, $this->lastResult) ) {
            throw new \UnexpectedValueException(sprintf('Input value "%s" is not properly validated.', $input));
        }

        $input = $this->lastResult;

        return new DateTimeExtended($input);
    }

    /**
     * {@inheritdoc}
     *
     * @param DateTimeExtended $value
     */
    public function formatOutput($value)
    {
        if (!$value instanceof \DateTime) {
            return $value;
        }

        $formatter = \IntlDateFormatter::create(
            \Locale::getDefault(),
            \IntlDateFormatter::SHORT,
            \IntlDateFormatter::NONE,
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
     * @param DateTimeExtended $input
     */
    public function dumpValue($input)
    {
        return $input->format('Y-m-d');
    }

    /**
     * {@inheritdoc}
     *
     * @param DateTimeExtended $input
     * @param DateTimeExtended $nextValue
     */
    public function isHigher($input, $nextValue)
    {
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
        return ($input->getTimestamp() < $nextValue->getTimestamp());
    }

    /**
     * {@inheritdoc}
     *
     * @param DateTimeExtended $input
     * @param DateTimeExtended $nextValue
     */
    public function isEquals($input, $nextValue)
    {
        return ($input->getTimestamp() === $nextValue->getTimestamp());
    }

    /**
     * {@inheritdoc}
     */
    public function validateValue($input, &$message = null)
    {
        $message = 'This value is not a valid date';

        return DateTimeHelper::validate($input, DateTimeHelper::ONLY_DATE, $this->lastResult);
    }

    /**
     * {@inheritdoc}
     */
    public function getMatcherRegex()
    {
        return DateTimeHelper::getMatcherRegex(DateTimeHelper::ONLY_DATE);
    }

    /**
     * {@inheritdoc}
     */
    public function sortValuesList(SingleValue $first, SingleValue $second)
    {
        $a = $first->getValue()->getTimestamp();
        $b = $second->getValue()->getTimestamp();

        if ($a === $b) {
            return 0;
        }

        return $a < $b ? -1 : 1;
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
        $date->modify('+1 day');

        return $date;
    }
}

/**
 * DateTimeExtended class for holding the dateTime with attentional information.
 *
 * @internal
 */
class DateTimeExtended extends \DateTime
{
    private $hasTime = false;
    private $hasSeconds = false;

    public function  __construct($time, $hasTime = false)
    {
        $this->hasTime = $hasTime;

        if ($hasTime && preg_match('#\d+:\d+:\d+$#', $time)) {
            $this->hasSeconds = true;
        }

        parent::__construct($time);
    }

    public function hasTime()
    {
        return $this->hasTime;
    }

    public function hasSeconds()
    {
        return $this->hasSeconds;
    }
}
