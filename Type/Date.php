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
class Date implements FilterTypeInterface, ValueMatcherInterface, ValuesToRangeInterface, ConfigurableTypeInterface, ChainableTypeInterface
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
    public function sanitizeString($input)
    {
        if (is_object($input)) {
            return $input;
        }

        if ($input !== $this->lastResult && !DateTimeHelper::validate($input, DateTimeHelper::ONLY_DATE, $this->lastResult)) {
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
    public function isEqual($input, $nextValue)
    {
        return ($input->getTimestamp() === $nextValue->getTimestamp());
    }

    /**
     * {@inheritdoc}
     */
    public function validateValue($input, &$message = null, MessageBag $messageBag = null)
    {
        $message = 'This value is not a valid date';

        if (DateTimeHelper::validateIso($input, DateTimeHelper::ONLY_DATE)) {
            $this->lastResult = $input;
        } elseif (!DateTimeHelper::validate($input, DateTimeHelper::ONLY_DATE, $this->lastResult)) {
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
    public function acceptInput($input)
    {
        return preg_match('#^' . $this->getMatcherRegex() . '$#uis', $input) > 0;
    }

    /**
     * Validates that the value is not lower then min/higher then max.
     *
     * @param string     $input
     * @param MessageBag $messageBag
     *
     * @return boolean
     */
    protected function validateHigherLower($input, MessageBag $messageBag = null)
    {
        if (null === $this->options['min'] && null === $this->options['max']) {
            return true;
        }

        $input = new DateTimeExtended($input, isset($this->hasTime) ? $this->hasTime : false);

        if (null !== $this->options['min'] && $this->isLower($input, $this->options['min'])) {
            $messageBag->addError('This value should be {{ limit }} or more', array('{{ limit }}' => $this->formatOutput($this->options['min'])), false);
        }

        if (null !== $this->options['max'] && $this->isHigher($input, $this->options['max'])) {
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
