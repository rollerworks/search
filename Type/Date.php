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

use Rollerworks\Bundle\RecordFilterBundle\Formatter\ValuesToRangeInterface;
use Rollerworks\Bundle\RecordFilterBundle\MessageBag;
use Rollerworks\Component\Locale\DateTime as DateTimeHelper;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\Options;

/**
 * Date filter type.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class Date implements FilterTypeInterface, ValueMatcherInterface, ValuesToRangeInterface, ConfigurableTypeInterface
{
    /**
     * @var string
     */
    protected $lastResult;

    /**
     * @var array
     */
    protected $options = array();

    /**
     * Constructor.
     *
     * @param array $options Array with min/max value as ISO formatted date(Time)
     *
     * @see setOptions()
     */
    public function __construct(array $options = array())
    {
        $this->setOptions($options);
    }

    /**
     * {@inheritdoc}
     *
     * @param array $options Array with min/max value as ISO formatted date(Time)
     *
     * @throws \UnexpectedValueException When min is higher then max
     */
    public function setOptions(array $options)
    {
        $optionsResolver = new OptionsResolver();
        static::setDefaultOptions($optionsResolver);

        $this->options = $optionsResolver->resolve($options);

        if (null !== $this->options['min'] && null !== $this->options['max'] && $this->options['min']->getTimestamp() >= $this->options['max']->getTimestamp()) {
            throw new \UnexpectedValueException(sprintf(
                    'Option min "%s" must not be lower or equal to option max "%s".',
                    $this->options['min']->format('Y-m-d H:i:s'),
                    $this->options['max']->format('Y-m-d H:i:s')
                )
            );
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return DateTimeExtended
     */
    public function sanitizeString($value)
    {
        if (is_object($value)) {
            return $value;
        }

        if ($value !== $this->lastResult && !DateTimeHelper::validate($value, DateTimeHelper::ONLY_DATE, $this->lastResult)) {
            throw new \UnexpectedValueException(sprintf('Input value "%s" is not properly validated.', $value));
        }

        $value = $this->lastResult;

        return new DateTimeExtended($value);
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
     * @param DateTimeExtended $value
     */
    public function dumpValue($value)
    {
        return $value->format('Y-m-d');
    }

    /**
     * {@inheritdoc}
     *
     * @param DateTimeExtended $value
     * @param DateTimeExtended $nextValue
     */
    public function isHigher($value, $nextValue)
    {
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
        return ($value->getTimestamp() < $nextValue->getTimestamp());
    }

    /**
     * {@inheritdoc}
     *
     * @param DateTimeExtended $value
     * @param DateTimeExtended $nextValue
     */
    public function isEqual($value, $nextValue)
    {
        return ($value->getTimestamp() === $nextValue->getTimestamp());
    }

    /**
     * {@inheritdoc}
     */
    public function validateValue($value, &$message = null, MessageBag $messageBag = null)
    {
        $message = 'This value is not a valid date';

        if (DateTimeHelper::validateIso($value, DateTimeHelper::ONLY_DATE)) {
            $this->lastResult = $value;
        } elseif (!DateTimeHelper::validate($value, DateTimeHelper::ONLY_DATE, $this->lastResult)) {
            return false;
        }

        if (!$this->validateHigherLower($this->lastResult, $messageBag)) {
            return false;
        }

        return true;
    }

    /**
     * Validates that the value is not lower then min/higher then max.
     *
     * @param string     $value
     * @param MessageBag $messageBag
     *
     * @return boolean
     */
    protected function validateHigherLower($value, MessageBag $messageBag = null)
    {
        if (null === $this->options['min'] && null === $this->options['max']) {
            return true;
        }

        $value = new DateTimeExtended($value, isset($this->hasTime) ? $this->hasTime : false);

        if (null !== $this->options['min'] && $this->isLower($value, $this->options['min'])) {
            $messageBag->addError('This value should be {{ limit }} or more', array('{{ limit }}' => $this->formatOutput($this->options['min'])), false);
        }

        if (null !== $this->options['max'] && $this->isHigher($value, $this->options['max'])) {
            $messageBag->addError('This value should be {{ limit }} or less', array('{{ limit }}' => $this->formatOutput($this->options['max'])), false);
        }

        if ($messageBag) {
            return !$messageBag->has('error');
        }

        return true;
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
    public function sortValuesList($first, $second)
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
     * @param DateTimeExtended $value
     *
     * @return DateTimeExtended
     */
    public function getHigherValue($value)
    {
        $date = clone $value;
        $date->modify('+1 day');

        return $date;
    }

    /**
     * {@inheritdoc}
     */
    public static function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'max' => null,
            'min' => null,
        ));

        /*
        $resolver->setAllowedTypes(array(
            'max' => array('DateTimeExtended', 'string', 'null'),
            'min' => array('DateTimeExtended', 'string', 'null')
        ));
        */

        // Convert the input to an DateTimeExtended object for comparison
        $valueFilter = function (Options $options, $value) {
            if (null === $value) {
                return $value;
            }

            // FIXME We need pre-validation (or something) for this
            if (!is_string($value)) {
                throw new \UnexpectedValueException(sprintf('Min/max value must be ISO formatted date(time) string, "%s" given instead.', gettype($value)));
            }

            return new DateTimeExtended($value, false !== strpos($value, ':'));
        };

        $resolver->setFilters(array(
            'max' => $valueFilter,
            'min' => $valueFilter
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions()
    {
        return $this->options;
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

    /**
     * @param string  $time
     * @param boolean $hasTime
     */
    public function  __construct($time, $hasTime = false)
    {
        $this->hasTime = $hasTime;

        if ($hasTime && preg_match('#\d+:\d+:\d+$#', $time)) {
            $this->hasSeconds = true;
        }

        parent::__construct($time);
    }

    /**
     * @return boolean
     */
    public function hasTime()
    {
        return $this->hasTime;
    }

    /**
     * @return boolean
     */
    public function hasSeconds()
    {
        return $this->hasSeconds;
    }
}
