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

use Rollerworks\RecordFilterBundle\Type\FilterTypeInterface;
use Rollerworks\RecordFilterBundle\Formatter\ValuesToRangeInterface;
use Rollerworks\RecordFilterBundle\Value\SingleValue;
use Rollerworks\RecordFilterBundle\MessageBag;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\Options;

/**
 * Integer filter type.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class Number implements FilterTypeInterface, ValuesToRangeInterface, ConfigurableInterface
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
     * @var \NumberFormatter|null
     */
    private static $numberFormatter = null;

    /**
     * Constructor.
     *
     * @param array $options Array with min/max value as integer or string
     *
     * @throws \UnexpectedValueException When min is higher then max
     */
    public function __construct(array $options = array())
    {
        $optionsResolver = new OptionsResolver();
        static::setOptions($optionsResolver);

        $this->options = $optionsResolver->resolve($options);

        if (null !== $this->options['min'] && null !== $this->options['max'] && ($this->isHigher($this->options['min'], $this->options['max']) || $this->isEqual($this->options['min'], $this->options['max']))) {
            throw new \UnexpectedValueException(sprintf('Option min "%s" must not be lower or equal to option max "%s".', $this->options['min'], $this->options['max']));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function sanitizeString($input)
    {
        // Note we explicitly don't cast the value to an integer type
        // 64bit integers are not properly handled on a 32bit OS

        if (ctype_digit((string) ltrim($input, '-+'))) {
            return ltrim($input, '+');
        }

        if ($input !== $this->lastResult && !$this->validateValue($input) ) {
            throw new \UnexpectedValueException(sprintf('Input value "%s" is not properly validated.', $input));
        }

        return $this->lastResult;
    }

    /**
     * {@inheritdoc}
     */
    public function formatOutput($value)
    {
        return self::getNumberFormatter(\Locale::getDefault())->format($value, \NumberFormatter::TYPE_INT64 | \NumberFormatter::GROUPING_USED);
    }

    /**
     * {@inheritdoc}
     */
    public function dumpValue($input)
    {
        return $input;
    }

    /**
     * {@inheritdoc}
     */
    public function isHigher($input, $nextValue)
    {
        $phpMax = strlen(PHP_INT_MAX) - 1;

        if ((strlen($input) > $phpMax || strlen($nextValue) > $phpMax) && function_exists('bccomp')) {
            return bccomp($input, $nextValue) === 1;
        }

        return ((integer) $input > (integer) $nextValue);
    }

    /**
     * {@inheritdoc}
     */
    public function isLower($input, $nextValue)
    {
        $phpMax = strlen(PHP_INT_MAX) - 1;

        if ((strlen($input) > $phpMax || strlen($nextValue) > $phpMax) && function_exists('bccomp')) {
            return bccomp($input, $nextValue) === -1;
        }

        return ((integer) $input < (integer) $nextValue);
    }

    /**
     * {@inheritdoc}
     */
    public function isEqual($input, $nextValue)
    {
        return ((string) $input === (string) $nextValue);
    }

    /**
     * {@inheritdoc}
     */
    public function validateValue($input, &$message = null, MessageBag $messageBag = null)
    {
        $message = 'This value is no valid number';

        if (!preg_match('/^(?:[+-]?(?:\p{N}+)|(?:\p{N}+[+-]?))$/us', (string) $input)) {
            return false;
        }

        if (ctype_digit((string) ltrim($input, '-+'))) {
            $this->lastResult = ltrim($input, '+');
        } elseif (!($this->lastResult = self::getNumberFormatter(\Locale::getDefault())->parse((string) trim($input, '+'), \NumberFormatter::TYPE_INT64))) {
            return false;
        }

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
    public function sortValuesList(SingleValue $first, SingleValue $second)
    {
        $phpMax = strlen(PHP_INT_MAX) - 1;

        if ((strlen($first->getValue()) > $phpMax || strlen($second->getValue()) > $phpMax) && function_exists('bccomp')) {
            return bccomp($first->getValue(), $second->getValue());
        }

        if ((integer) $first->getValue() === (integer) $second->getValue()) {
            return 0;
        }

        return ((integer) $first->getValue() < (integer) $second->getValue() ? -1 : 1);
    }

    /**
     * {@inheritdoc}
     */
    public function getHigherValue($input)
    {
        $phpMax = strlen(PHP_INT_MAX) - 1;

        if (strlen($input) > $phpMax && function_exists('bcadd')) {
            return bcadd(ltrim($input, '+'), '1');
        }

        return (intval($input) + 1);
    }

    /**
     * {@inheritdoc}
     */
    public static function setOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'max' => null,
            'min' => null,
        ));

        $resolver->setAllowedTypes(array(
            'max' => array('string', 'int', 'null'),
            'min' => array('string', 'int', 'null')
        ));
    }

    /**
     * Returns a shared NumberFormatter object.
     *
     * @param null|string $locale
     *
     * @return \NumberFormatter
     */
    protected static function getNumberFormatter($locale = null)
    {
        $locale = $locale ?: \Locale::getDefault();

        if (null === self::$numberFormatter || self::$numberFormatter->getLocale() !== $locale) {
            self::$numberFormatter = new \NumberFormatter($locale, \NumberFormatter::DECIMAL);
        }

        return self::$numberFormatter;
    }
}
