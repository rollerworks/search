<?php

/*
 * This file is part of the RollerworksRecordFilterBundle package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Type;

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
     * @var integer
     */
    protected $lastResult;

    /**
     * @var array
     */
    protected $options = array();

    /**
     * @var \NumberFormatter|null
     */
    protected static $numberFormatter;

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
        if (ctype_digit((string) ltrim($value, '-+'))) {
            return ltrim($value, '+');
        }

        if ($value !== $this->lastResult && !$this->validate($value)) {
            throw new \UnexpectedValueException(sprintf('Input value "%s" is not properly validated.', $value));
        }

        return $this->lastResult;
    }

    /**
     * {@inheritdoc}
     */
    public function formatOutput($value, $formatGrouping = true)
    {
        if (static::isBigNumber($value)) {
            return $value;
        }

        $numberFormatter = static::getNumberFormatter(null, true);
        $formatGrouping = (($formatGrouping && $this->options['format_grouping'] !== false) || true === $this->options['format_grouping']);
        $numberFormatter->setAttribute(\NumberFormatter::GROUPING_USED, $formatGrouping);

        return $numberFormatter->format($value, \NumberFormatter::TYPE_INT64);
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
        if (static::isBigNumber($value) || static::isBigNumber($nextValue) && function_exists('bccomp')) {
            return bccomp($value, $nextValue) === 1;
        }

        return $value > $nextValue;
    }

    /**
     * {@inheritdoc}
     */
    public function isLower($value, $nextValue)
    {
        if (static::isBigNumber($value) || static::isBigNumber($nextValue) && function_exists('bccomp')) {
            return bccomp($value, $nextValue) === -1;
        }

        return $value < $nextValue;
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
    public function validateValue($value, MessageBag $messageBag)
    {
        if (!$this->validate($value)) {
            $messageBag->addError('This value is not a valid number.');

            return ;
        }

        if (null !== $this->options['min'] && $this->isLower($value, $this->options['min'])) {
            $messageBag->addError('This value should be {{ limit }} or more.', array('{{ limit }}' => $this->formatOutput($this->options['min'])));
        }

        if (null !== $this->options['max'] && $this->isHigher($value, $this->options['max'])) {
            $messageBag->addError('This value should be {{ limit }} or less.', array('{{ limit }}' => $this->formatOutput($this->options['max'])));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getHigherValue($value)
    {
        if (static::isBigNumber($value) && function_exists('bcadd')) {
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
            'format_grouping' => true,
        ));

        $resolver->setAllowedTypes(array(
            'max' => array('string', 'int', 'null'),
            'min' => array('string', 'int', 'null'),
            'format_grouping' => array('bool'),
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Validates the value format and tries to parse it.
     *
     * @param string $value
     *
     * @return boolean
     */
    protected function validate($value)
    {
        if (ctype_digit((string) ltrim($value, '-+'))) {
            $this->lastResult = ltrim($value, '+');
        } elseif (false === ($this->lastResult = static::getNumberFormatter()->parse($value, \NumberFormatter::TYPE_INT64))) {
            return false;
        }

        return true;
    }

    /**
     * Returns a shared NumberFormatter object.
     *
     * @param null|string $locale
     * @param boolean     $forceNew Creates an new object (but leaves the current)
     * @param integer     $type
     *
     * @return \NumberFormatter
     */
    protected static function getNumberFormatter($locale = null, $forceNew = false, $type = \NumberFormatter::DECIMAL)
    {
        $locale = $locale ?: \Locale::getDefault();
        if ($forceNew) {
            return new \NumberFormatter($locale, $type);
        }

        if (null === static::$numberFormatter || static::$numberFormatter->getLocale() !== $locale) {
            static::$numberFormatter = new \NumberFormatter($locale, $type);
        }

        return static::$numberFormatter;
    }

    protected static function isBigNumber($value)
    {
        if (is_int($value)) {
            return false;
        }

        if (strlen($value) > strlen(PHP_INT_MAX) - 1) {
            return true;
        }

        return false;
    }
}
