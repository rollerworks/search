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

use Rollerworks\Component\Locale\BigNumber;
use Rollerworks\Bundle\RecordFilterBundle\Type\FilterTypeInterface;
use Rollerworks\Bundle\RecordFilterBundle\Formatter\ValuesToRangeInterface;
use Rollerworks\Bundle\RecordFilterBundle\MessageBag;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Integer filter-type.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class Number implements FilterTypeInterface, ValuesToRangeInterface, ConfigurableTypeInterface
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
     * @param array $options Array with min/max value as integer or string
     */
    public function __construct(array $options = array())
    {
        $this->setOptions($options);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \UnexpectedValueException When min is higher then max
     */
    public function setOptions(array $options)
    {
        $optionsResolver = new OptionsResolver();
        static::setDefaultOptions($optionsResolver);

        $this->options = $optionsResolver->resolve($options);
        if (null !== $this->options['min'] && null !== $this->options['max'] && ($this->isHigher($this->options['min'], $this->options['max']) || $this->isEqual($this->options['min'], $this->options['max']))) {
            throw new \UnexpectedValueException(sprintf('Option min "%s" must not be lower or equal to option max "%s".', $this->options['min'], $this->options['max']));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function sanitizeString($value)
    {
        // Note we explicitly don't cast the value to an integer type
        // 64bit integers are not properly handled on a 32bit OS

        if (ctype_digit((string) ltrim($value, '-+'))) {
            return ltrim($value, '+');
        }

        if ($value !== $this->lastResult && !$this->validateValue($value) ) {
            throw new \UnexpectedValueException(sprintf('Input value "%s" is not properly validated.', $value));
        }

        return $this->lastResult;
    }

    /**
     * {@inheritdoc}
     */
    public function formatOutput($value)
    {
        return BigNumber::format($value, \Locale::getDefault(), true);
    }

    /**
     * {@inheritdoc}
     */
    public function dumpValue($value)
    {
        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function isHigher($value, $nextValue)
    {
        $phpMax = strlen(PHP_INT_MAX) - 1;
        if ((strlen($value) > $phpMax || strlen($nextValue) > $phpMax) && function_exists('bccomp')) {
            return bccomp($value, $nextValue) === 1;
        }

        return ((integer) $value > (integer) $nextValue);
    }

    /**
     * {@inheritdoc}
     */
    public function isLower($value, $nextValue)
    {
        $phpMax = strlen(PHP_INT_MAX) - 1;

        if ((strlen($value) > $phpMax || strlen($nextValue) > $phpMax) && function_exists('bccomp')) {
            return bccomp($value, $nextValue) === -1;
        }

        return ((integer) $value < (integer) $nextValue);
    }

    /**
     * {@inheritdoc}
     */
    public function isEqual($value, $nextValue)
    {
        return ((string) $value === (string) $nextValue);
    }

    /**
     * {@inheritdoc}
     */
    public function validateValue($value, &$message = null, MessageBag $messageBag = null)
    {
        $message = 'This value is not a valid number.';

        if (ctype_digit((string) ltrim($value, '-+'))) {
            $this->lastResult = ltrim($value, '+');
        } elseif (null === ($this->lastResult = BigNumber::parse((string) trim($value, '+')))) {
            return false;
        }

        $message = null;

        if (null !== $this->options['min'] && $this->isLower($value, $this->options['min'])) {
            $messageBag->addError('This value should be {{ limit }} or more.', array('{{ limit }}' => $this->formatOutput($this->options['min'])), false, true, 'validators');
        }

        if (null !== $this->options['max'] && $this->isHigher($value, $this->options['max'])) {
            $messageBag->addError('This value should be {{ limit }} or less.', array('{{ limit }}' => $this->formatOutput($this->options['max'])), false, true, 'validators');
        }

        if ($messageBag) {
            return !$messageBag->has('error');
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function sortValuesList($first, $second)
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
    public function getHigherValue($value)
    {
        $phpMax = strlen(PHP_INT_MAX) - 1;
        if (strlen($value) > $phpMax && function_exists('bcadd')) {
            return bcadd(ltrim($value, '+'), '1');
        }

        return (intval($value) + 1);
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

        $resolver->setAllowedTypes(array(
            'max' => array('string', 'int', 'null'),
            'min' => array('string', 'int', 'null')
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
