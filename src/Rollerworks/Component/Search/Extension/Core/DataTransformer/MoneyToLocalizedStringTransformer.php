<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search\Extension\Core\DataTransformer;

use Rollerworks\Component\Search\Exception\TransformationFailedException;
use Rollerworks\Component\Search\Extension\Core\Model\MoneyValue;

/**
 * Transforms between a normalized format and a localized money string.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 * @author Florian Eckerstorfer <florian@eckerstorfer.org>
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class MoneyToLocalizedStringTransformer extends NumberToLocalizedStringTransformer
{
    /**
     * @var integer|null
     */
    private $divisor;

    /**
     * @var string
     */
    private $defaultCurrency;

    /**
     * @param integer $precision
     * @param boolean $grouping
     * @param integer $roundingMode
     * @param integer $divisor
     * @param string  $defaultCurrency
     */
    public function __construct($precision = null, $grouping = null, $roundingMode = null, $divisor = null, $defaultCurrency = null)
    {
        if (null === $grouping) {
            $grouping = true;
        }

        if (null === $precision) {
            $precision = 2;
        }

        parent::__construct($precision, $grouping, $roundingMode, \NumberFormatter::TYPE_CURRENCY);

        if (null === $divisor) {
            $divisor = 1;
        }

        $this->divisor = $divisor;
        $this->defaultCurrency = $defaultCurrency;
    }

    /**
     * Transforms a normalized format into a localized money string.
     *
     * @param MoneyValue $value Normalized number
     *
     * @return string Localized money string.
     *
     * @throws TransformationFailedException If the given value is not numeric or
     *                                       if the value can not be transformed.
     */
    public function transform($value)
    {
        if (null === $value) {
            return '';
        }

        if (!$value instanceof MoneyValue) {
            throw new TransformationFailedException('Expected a MoneyValue object.');
        }

        if (!is_numeric($value->value)) {
            throw new TransformationFailedException('Expected a numeric value.');
        }

        $amountValue = $value->value;
        $amountValue /= $this->divisor;

        $formatter = $this->getNumberFormatter();
        $value = $formatter->formatCurrency($amountValue, $value->currency);

        if (intl_is_failure($formatter->getErrorCode())) {
            throw new TransformationFailedException($formatter->getErrorMessage());
        }

        // Convert fixed spaces to normal ones
        $value = str_replace("\xc2\xa0", ' ', $value);

        return $value;
    }

    /**
     * Transforms a localized money string into a normalized format.
     *
     * @param string $value Localized money string
     *
     * @return MoneyValue Normalized number
     *
     * @throws TransformationFailedException If the given value is not a string
     *                                       or if the value can not be transformed.
     */
    public function reverseTransform($value)
    {
        $value = str_replace(' ', "\xc2\xa0", $value);

        if (!preg_match('#\p{Sc}#u', $value)) {
            $currency = false;
        }

        $value = parent::reverseTransform($value, $currency);

        if (null !== $value) {
            $value *= $this->divisor;
        }

        if (false === $currency) {
            $currency = $this->defaultCurrency;
        }

        return new MoneyValue($currency, (string) $value);
    }
}
