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
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * DateTime filter type.
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
    public function sanitizeString($input)
    {
        if (is_object($input)) {
            return $input;
        }

        $hasTime = false;

        if ($input !== $this->lastResult && !DateTimeHelper::validate($input, ($this->options['time_optional'] ? DateTimeHelper::ONLY_DATE_OPTIONAL_TIME : DateTimeHelper::ONLY_DATE_TIME), $this->lastResult, $hasTime) ) {
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
     * @param \DateTime $input
     */
    public function dumpValue($input)
    {
        return $input->format('Y-m-d\TH:i:s');
    }

    /**
     * {@inheritdoc}
     */
    public function validateValue($input, &$message = null, MessageBag $messageBag = null)
    {
        $message = 'This value is not a valid date with ' . ($this->options['time_optional'] ? 'optional ' : '') . 'time';

        if (DateTimeHelper::validateIso($input, ($this->options['time_optional'] ? DateTimeHelper::ONLY_DATE_OPTIONAL_TIME : DateTimeHelper::ONLY_DATE_TIME), $this->hasTime)) {
            $this->lastResult = $input;
        } elseif (!DateTimeHelper::validate($input, ($this->options['time_optional'] ? DateTimeHelper::ONLY_DATE_OPTIONAL_TIME : DateTimeHelper::ONLY_DATE_TIME), $this->lastResult, $this->hasTime)) {
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

    /**
     * {@inheritdoc}
     */
    public static function setOptions(OptionsResolverInterface $resolver)
    {
        parent::setOptions($resolver);

        $resolver->setDefaults(array(
            'time_optional' => false,
        ));

        $resolver->setAllowedTypes(array(
            'time_optional' => 'bool'
        ));
    }
}
