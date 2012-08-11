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
use Rollerworks\Bundle\RecordFilterBundle\Formatter\ValuesToRangeInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\Options;

/**
 * Decimal filter type.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class Decimal implements FilterTypeInterface, ValueMatcherInterface, ValuesToRangeInterface, ConfigurableTypeInterface
{
    /**
     * @var string
     */
    protected $lastResult;

    /**
     * @var \NumberFormatter|null
     */
    private static $numberFormatter = null;

    /**
     * @var array
     */
    protected $options = array();

    /**
     * Constructor.
     *
     * @param array $options
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
        // Note we explicitly don't cast the value to an float type
        // 64bit floats are not properly handled on a 32bit OS

        if (!preg_match('/[^.0-9-]/', $value)) {
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
    public function formatOutput($value, $formatGrouping = true)
    {
        $phpMax = strlen(PHP_INT_MAX) - 1;

        $numberFormatter = self::getNumberFormatter(\Locale::getDefault(), true);

        if (is_string($value) && strlen($value) > $phpMax) {
            list($digit, $fraction) = explode('.', $value);

            $decimalSign = $numberFormatter->getSymbol(\NumberFormatter::DECIMAL_SEPARATOR_SYMBOL);

            $digit    = self::formatBigInt($digit);
            $fraction = self::formatBigInt($fraction);

            if (($formatGrouping && $this->options['format_grouping'] !== false) || true === $this->options['format_grouping']) {
                $digit = preg_replace('/(\p{N})(?=(\p{N}\p{N}\p{N})+(?!\p{N}))/u', '$1' . $numberFormatter->getSymbol(\NumberFormatter::GROUPING_SEPARATOR_SYMBOL), $digit);
            }

            if (null === $this->options['min_fraction_digits']) {
                $this->options['min_fraction_digits'] = $numberFormatter->getAttribute(\NumberFormatter::MIN_FRACTION_DIGITS);
            }

            if (null === $this->options['max_fraction_digits']) {
                $this->options['max_fraction_digits'] = $numberFormatter->getAttribute(\NumberFormatter::MAX_FRACTION_DIGITS);
            }

            if ($this->options['min_fraction_digits'] > mb_strlen($fraction)) {
                $fraction = str_pad($fraction, $this->options['min_fraction_digits'], $numberFormatter->format(0));
            }

            if (mb_strlen($fraction) > $this->options['max_fraction_digits']) {
                $fraction = mb_substr($fraction, 0, $this->options['max_fraction_digits']);
            }

            if ('-' === $value[0]) {
                if ('-' === substr($numberFormatter->format(-123), 0, 1)) {
                    return '-' . mb_substr($digit, 1) . $decimalSign .  $fraction;
                } else {
                    return $digit . $decimalSign .  $fraction . '-';
                }
            }

            return $digit . $decimalSign .  $fraction;
        } else {
            $this->setFractions($numberFormatter);

            if (($formatGrouping && $this->options['format_grouping'] !== false) || true === $this->options['format_grouping']) {
                return $numberFormatter->format($value, \NumberFormatter::TYPE_DOUBLE | \NumberFormatter::GROUPING_USED);
            } else {
                return $numberFormatter->format($value, \NumberFormatter::TYPE_DOUBLE);
            }
        }
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

        return ((float) $value > (float) $nextValue);
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

        return ((float) $value < (float) $nextValue);
    }

    /**
     * {@inheritdoc}
     */
    public function isEqual($value, $nextValue)
    {
        $phpMax = strlen(PHP_INT_MAX) - 1;

        if ((strlen($value) > $phpMax || strlen($nextValue) > $phpMax) && function_exists('gmp_cmp')) {
            return bccomp($value, $nextValue) === 0;
        }

        return ((float) $value == (float) $nextValue);
    }

    /**
     * {@inheritdoc}
     */
    public function validateValue($value, &$message = null, MessageBag $messageBag = null)
    {
        $message = 'This value is not an valid decimal.';

        $numberFormatter = self::getNumberFormatter(\Locale::getDefault());
        $this->setFractions($numberFormatter);
        $decimalSign = $numberFormatter->getSymbol(\NumberFormatter::DECIMAL_SEPARATOR_SYMBOL);

        if (!preg_match('/^(?P<char_left>-)?(?P<left_digit>\p{N}+)' . preg_quote($decimalSign, '/') . '(?P<right_digit>\p{N}+)(?P<char_right>-)?$/u', $value, $matched)) {
            return false;
        }

        if (!empty($matched['char_left']) && !empty($matched['char_right'])) {
            return false;
        }

        $phpMax = strlen(PHP_INT_MAX) - 1;

        if (strlen($matched['left_digit']) > $phpMax || strlen($matched['right_digit']) > $phpMax) {
            if (!($first = self::parseBigInt($matched['left_digit']))) {
                return false;
            }

            if (!($second = self::parseBigInt($matched['right_digit']))) {
                return false;
            }

            $this->lastResult = '';

            if (!empty($matched['char_left']) || !empty($matched['char_right'])) {
                $this->lastResult = '-';
            }

            $this->lastResult .= $first. '.' . $second;
        } elseif (!($this->lastResult = self::getNumberFormatter(\Locale::getDefault())->parse($value, \NumberFormatter::TYPE_DOUBLE))) {
            return false;
        }

        $message = null;

        if (null !== $this->options['min'] && $this->isLower($this->lastResult, $this->options['min'])) {
            $messageBag->addError('This value should be {{ limit }} or more.', array('{{ limit }}' => $this->formatOutput($this->options['min'])), false);
        }

        if (null !== $this->options['max'] && $this->isHigher($this->lastResult, $this->options['max'])) {
            $messageBag->addError('This value should be {{ limit }} or less.', array('{{ limit }}' => $this->formatOutput($this->options['max'])), false);
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

        if ((float) $first->getValue() === (float) $second->getValue()) {
            return 0;
        }

        return ((float) $first->getValue() < (float) $second->getValue()) ? -1 : 1;
    }

    /**
     * {@inheritdoc}
     */
    public function getHigherValue($value)
    {
        $phpMax = strlen(PHP_INT_MAX) - 1;

        if (strlen($value) > $phpMax && function_exists('bcadd')) {
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

        // Allow dot as replacement for comma
        if ($decimalSign === ',') {
            $decimalSign = '[,.]';
        }

        return '(?:(?:\p{N}+' . $decimalSign . '\p{N}+[-+]?|[-+]?\p{N}+' . $decimalSign . '\p{N}+))';
    }

    /**
     * {@inheritdoc}
     */
    public static function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'max' => null,
            'min' => null,

            'min_fraction_digits' => null,
            'max_fraction_digits' => null,
            'format_grouping'     => true,
        ));

        $resolver->setAllowedTypes(array(
            'max' => array('string', 'int', 'null'),
            'min' => array('string', 'int', 'null'),

            'min_fraction_digits' => array('int', 'null'),
            'max_fraction_digits' => array('int', 'null'),

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
     * @param \NumberFormatter $numberFormatter
     */
    protected function setFractions(\NumberFormatter $numberFormatter)
    {
        if (null !== $this->options['min_fraction_digits']) {
            $numberFormatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, $this->options['min_fraction_digits']);
        }

        if (null !== $this->options['max_fraction_digits']) {
            $numberFormatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, $this->options['max_fraction_digits']);
        }
    }

    /**
     * Parses BigInt.
     *
     * @param string      $value
     * @param null|string $locale
     *
     * @return string|boolean
     */
    protected static function parseBigInt($value, $locale = null)
    {
        $numberFormatter = self::getNumberFormatter($locale, true);
        $value = str_replace(array(',', '.', ' '), '', $value);

        $result = preg_replace_callback('/(\p{N})/u', function($match) use ($numberFormatter) {
            /** @var \NumberFormatter $numberFormatter */
            if (ctype_digit($match[1])) {
                return $match[1];
            }

            return (string) $numberFormatter->parse($match[1], \NumberFormatter::TYPE_INT32);
        }, $value);

        if (!ctype_digit($result)) {
            return false;
        }

        return $result;
    }

    /**
     * Formats a BigInt to localized number.
     *
     * @param string      $value
     * @param null|string $locale
     *
     * @return string
     */
    protected function formatBigInt($value, $locale = null)
    {
        $numberFormatter = self::getNumberFormatter($locale, true);

        // Output it not unicode so use as-is
        if (ctype_digit($numberFormatter->format('123'))) {
            return $value;
        }

        return preg_replace_callback('/(.)/', function($match) use ($numberFormatter) {
            /** @var \NumberFormatter $numberFormatter */

            return (string) $numberFormatter->format($match[1], \NumberFormatter::TYPE_INT32);
        }, $value);
    }

    /**
     * Returns a shared NumberFormatter object.
     *
     * @param null|string $locale
     * @param boolean     $forceNew Creates an new object (but leaves the current)
     *
     * @return \NumberFormatter
     */
    protected static function getNumberFormatter($locale = null, $forceNew = false)
    {
        $locale = $locale ?: \Locale::getDefault();

        if ($forceNew) {
            return new \NumberFormatter($locale, \NumberFormatter::DECIMAL);
        }

        if (null === self::$numberFormatter || self::$numberFormatter->getLocale() !== $locale) {
            self::$numberFormatter = new \NumberFormatter($locale, \NumberFormatter::DECIMAL);
        }

        return self::$numberFormatter;
    }
}
