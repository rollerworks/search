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
use Rollerworks\Component\Locale\BigNumber;

/**
 * Birthday/age filter-type.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class Birthday implements FilterTypeInterface, ValueMatcherInterface
{
    /**
     * @var string
     */
    protected $lastResult;

    /**
     * @var string
     */
    protected $lastInput;

    /**
     * {@inheritdoc}
     *
     * @return DateTimeExtended|integer
     */
    public function sanitizeString($value)
    {
        if (is_object($value) || is_int($value) || ctype_digit($value)) {
            return $value;
        }

        if ($value !== $this->lastInput && !$this->validateValue($value, $message)) {
            throw new \UnexpectedValueException(sprintf('Input value "%s" is not properly validated. Message: ' . $message, $value));
        }

        return $this->lastResult;
    }

    /**
     * {@inheritdoc}
     *
     * @param DateTimeExtended $value
     */
    public function formatOutput($value)
    {
        if (!$value instanceof \DateTime) {
            return BigNumber::format($value, false);
        }

        $formatter = \IntlDateFormatter::create(
            \Locale::getDefault(),
            \IntlDateFormatter::SHORT,
            \IntlDateFormatter::NONE,
            date_default_timezone_get(),
            \IntlDateFormatter::GREGORIAN
        );

        // Make the year always four digit
        $formatter->setPattern(str_replace(array('yy', 'yyyyyyyy'), 'yyyy', $formatter->getPattern()));

        return $formatter->format($value);
    }

    /**
     * {@inheritdoc}
     *
     * @param DateTimeExtended|integer $value
     */
    public function dumpValue($value)
    {
        if ($value instanceof \DateTime) {
            return $value->format('Y-m-d');
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     *
     * @param DateTimeExtended|integer $value
     * @param DateTimeExtended|integer $nextValue
     */
    public function isHigher($value, $nextValue)
    {
        if (!is_object($value) XOR !is_object($nextValue)) {
            return false;
        }

        if (is_object($value)) {
            return ($value->getTimestamp() > $nextValue->getTimestamp());
        }

        if ($value > $nextValue) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @param DateTimeExtended|integer $value
     * @param DateTimeExtended|integer $nextValue
     */
    public function isLower($value, $nextValue)
    {
        if (!is_object($value) XOR !is_object($nextValue)) {
            return false;
        }

        if (is_object($value)) {
            return ($value->getTimestamp() < $nextValue->getTimestamp());
        }

        if ($value < $nextValue) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @param DateTimeExtended|integer $value
     * @param DateTimeExtended|integer $nextValue
     */
    public function isEqual($value, $nextValue)
    {
        if (!is_object($value) XOR !is_object($nextValue)) {
            return false;
        }

        if (is_object($value)) {
            return ($value->getTimestamp() === $nextValue->getTimestamp());
        }

        if ($value === $nextValue) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function validateValue($value, &$message = null, MessageBag $messageBag = null)
    {
        if (is_int($value) || ctype_digit($value)) {
            return true;
        }

        if (preg_match('/^(\p{N}+)$/u', $value)) {
            $this->lastResult = BigNumber::parse($value);

            return true;
        }

        if (DateTimeHelper::validateIso($value, DateTimeHelper::ONLY_DATE)) {
            $this->lastResult = new DateTimeExtended($value);
        } elseif (!DateTimeHelper::validate($value, DateTimeHelper::ONLY_DATE, $this->lastResult)) {
            $message = 'This value is not a valid birthday or age.';

            return false;
        }

        if (!is_object($this->lastResult)) {
            $this->lastResult = new DateTimeExtended($this->lastResult);
        }

        if ($this->lastResult->getTimestamp() > time()) {
            $message = 'This value is not a valid birthday or age.';

            return false;
        }

        $this->lastInput = $value;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getMatcherRegex()
    {
        return '(?:' . DateTimeHelper::getMatcherRegex(DateTimeHelper::ONLY_DATE) . '|\p{N}+)';
    }
}
