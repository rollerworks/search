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
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * DateTime filter-type.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class DateTime extends Date
{
    /**
     * @var boolean
     */
    protected $hasTime = false;

    /**
     * {@inheritdoc}
     */
    public function sanitizeString($value)
    {
        if (is_object($value)) {
            return $value;
        }

        $hasTime = false;

        if ($value !== $this->lastResult && !DateTimeHelper::validate($value, ($this->options['time_optional'] ? DateTimeHelper::ONLY_DATE_OPTIONAL_TIME : DateTimeHelper::ONLY_DATE_TIME), $this->lastResult, $hasTime) ) {
            throw new \UnexpectedValueException(sprintf('Input value "%s" is not properly validated.', $value));
        }

        $value = $this->lastResult;

        return new DateTimeExtended($value, $hasTime);
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
            ($value->hasSeconds() ? \IntlDateFormatter::MEDIUM : ($value->hasTime() ? \IntlDateFormatter::SHORT : \IntlDateFormatter::NONE)),
            date_default_timezone_get(),
            \IntlDateFormatter::GREGORIAN
        );

        // Make year always four digit
        $pattern = str_replace(array('yy', 'yyyyyyyy'), 'yyyy', $formatter->getPattern());

        $formatter->setPattern($pattern);

        return $formatter->format($value);
    }

    /**
     * {@inheritdoc}
     *
     * @param \DateTime $value
     */
    public function dumpValue($value)
    {
        return $value->format('Y-m-d\TH:i:s');
    }

    /**
     * {@inheritdoc}
     */
    public function validateValue($value, &$message = null, MessageBag $messageBag = null)
    {
        if (DateTimeHelper::validateIso($value, ($this->options['time_optional'] ? DateTimeHelper::ONLY_DATE_OPTIONAL_TIME : DateTimeHelper::ONLY_DATE_TIME), $this->hasTime)) {
            $this->lastResult = $value;
        } elseif (!DateTimeHelper::validate($value, ($this->options['time_optional'] ? DateTimeHelper::ONLY_DATE_OPTIONAL_TIME : DateTimeHelper::ONLY_DATE_TIME), $this->lastResult, $this->hasTime)) {
            $message = 'This value is not a valid date' . ($this->options['time_optional'] ? ' with optional ' : '') . 'time.';

            return false;
        }

        if (!$this->validateHigherLower($this->lastResult, $messageBag)) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getMatcherRegex()
    {
        return DateTimeHelper::getMatcherRegex(($this->options['time_optional'] ? DateTimeHelper::ONLY_DATE_OPTIONAL_TIME : DateTimeHelper::ONLY_DATE_TIME));
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

        if (!$value->hasTime()) {
            $date->modify('+1 day');
        } elseif ($value->hasSeconds()) {
            $date->modify('+1 second');
        } else {
            $date->modify('+1 minute');
        }

        return $date;
    }

    /**
     * {@inheritdoc}
     */
    public static function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);

        $resolver->setDefaults(array(
            'time_optional' => false,
        ));

        $resolver->setAllowedTypes(array(
            'time_optional' => 'bool'
        ));
    }
}
