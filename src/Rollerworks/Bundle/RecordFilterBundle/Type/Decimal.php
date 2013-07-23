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

use Rollerworks\Component\Locale\BigFloat;
use Rollerworks\Bundle\RecordFilterBundle\MessageBag;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\Options;

/**
 * Decimal filter-type.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class Decimal extends Number
{
    /**
     * @var \NumberFormatter|null
     */
    protected static $numberFormatter;

    /**
     * {@inheritdoc}
     *
     * $options is an array with:
     *  * (string|integer) min/max value.
     *  * (integer) min_fraction_digits Minimum fraction digits.
     *  * (integer) max_fraction_digits Maximum fraction digits.
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

        if (null !== $this->options['min_fraction_digits'] && null !== $this->options['max_fraction_digits'] && $this->isHigher($this->options['min_fraction_digits'], $this->options['max_fraction_digits'])) {
            throw new \UnexpectedValueException(sprintf('Option min_fraction_digits "%s" must not be lower then option max_fraction_digits "%s".', $this->options['min_fraction_digits'], $this->options['max_fraction_digits']));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function sanitizeString($value)
    {
        if (!preg_match('/[^.0-9-]/', $value)) {
            return ltrim($value, '+');
        }

        if ($value !== $this->lastResult && false === ($this->lastResult = self::getNumberFormatter()->parse($value, \NumberFormatter::TYPE_DOUBLE))) {
            throw new \UnexpectedValueException(sprintf('Input value "%s" is not properly validated.', $value));
        }

        return $this->lastResult;
    }

    /**
     * {@inheritdoc}
     */
    public function formatOutput($value, $formatGrouping = true)
    {
        if (self::isBigNumber($value)) {
            return $value;
        }

        $formatGrouping = (($formatGrouping && $this->options['format_grouping'] !== false) || true === $this->options['format_grouping']);

        $numberFormatter = self::getNumberFormatter();
        $numberFormatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, $this->options['min_fraction_digits']);
        $numberFormatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $this->options['max_fraction_digits']);
        $numberFormatter->setAttribute(\NumberFormatter::GROUPING_USED, $formatGrouping);

        return $numberFormatter->format($value, \NumberFormatter::TYPE_DOUBLE);
    }

    /**
     * {@inheritdoc}
     */
    public function isEqual($value, $nextValue)
    {
        if (is_string($value) XOR is_string($nextValue) && function_exists('bccomp')) {
            return bccomp($value, $nextValue) === 0;
        }

        return ((float) $value == (float) $nextValue);
    }

    /**
     * {@inheritdoc}
     */
    public function validateValue($value, MessageBag $messageBag)
    {
        if (preg_match('/^-?[0-9]+\.[0-9]+$/', $value)) {
            $this->lastResult = (self::isBigNumber($value) ? $value : (float) $value);
        } else {
            $numberFormatter = self::getNumberFormatter();
            $numberFormatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, $this->options['min_fraction_digits']);
            $numberFormatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $this->options['max_fraction_digits']);

            if (false === ($this->lastResult = $numberFormatter->parse($value, \NumberFormatter::TYPE_DOUBLE))) {
                $messageBag->addError('This value is not a valid decimal.');

                return;
            }
        }

        if (null !== $this->options['min'] && $this->isLower($this->lastResult, $this->options['min'])) {
            $messageBag->addError('This value should be {{ limit }} or more.', array('{{ limit }}' => $this->formatOutput($this->options['min'])));
        }

        if (null !== $this->options['max'] && $this->isHigher($this->lastResult, $this->options['max'])) {
            $messageBag->addError('This value should be {{ limit }} or less.', array('{{ limit }}' => $this->formatOutput($this->options['max'])));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getHigherValue($value)
    {
        if (self::isBigNumber($value) && function_exists('bcadd')) {
            return bcadd(ltrim($value, '+'), '0.01');
        }

        return ((float) $value) + 0.01;
    }

    /**
     * {@inheritdoc}
     */
    public function getMatcherRegex()
    {
        $decimalSign = preg_quote(self::getNumberFormatter()->getSymbol(\NumberFormatter::DECIMAL_SEPARATOR_SYMBOL), '#');
        $minusSign   = preg_quote(self::getNumberFormatter()->getSymbol(\NumberFormatter::MINUS_SIGN_SYMBOL), '#');

        // Allow dot as replacement for comma
        if ($decimalSign === ',') {
            $decimalSign = '[,.]';
        }

        return '(?:(?:\p{N}+' . $decimalSign . '\p{N}+[' . $minusSign .'\-+]?|[' . $minusSign .'\-+]?\p{N}+' . $decimalSign . '\p{N}+))';
    }

    /**
     * {@inheritdoc}
     */
    public static function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'max' => null,
            'min' => null,
            'format_grouping'     => true,
        ));

        $numberFormatter = self::getNumberFormatter();

        $resolver->setDefaults(array(
            'min_fraction_digits' => function (Options $options) use ($numberFormatter) {
                return $numberFormatter->getAttribute(\NumberFormatter::MIN_FRACTION_DIGITS);
            },

            'max_fraction_digits' => function (Options $options) use ($numberFormatter) {
                return $numberFormatter->getAttribute(\NumberFormatter::MAX_FRACTION_DIGITS);
            },
        ));

        $resolver->setAllowedTypes(array(
            'max' => array('string', 'int', 'float', 'null'),
            'min' => array('string', 'int', 'float', 'null'),

            'min_fraction_digits' => array('int'),
            'max_fraction_digits' => array('int'),

            'format_grouping' => array('bool'),
        ));
    }

    protected static function isBigNumber($value)
    {
        if (is_int($value) || is_float($value)) {
            return false;
        }

        if (strlen(substr($value, 0, strpos($value, '.'))) > strlen(PHP_INT_MAX) - 1) {
            return true;
        }

        return false;
    }
}
