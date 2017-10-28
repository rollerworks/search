<?php

declare(strict_types=1);

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests\Extension\Core\DataTransformer;

use Money\Currencies\ISOCurrencies;
use Money\Parser\DecimalMoneyParser;
use PHPUnit\Framework\TestCase;
use Rollerworks\Component\Search\Exception\TransformationFailedException;
use Rollerworks\Component\Search\Extension\Core\DataTransformer\MoneyToStringTransformer;
use Rollerworks\Component\Search\Extension\Core\Model\MoneyValue;

/**
 * @internal
 */
final class MoneyToStringTransformerTest extends TestCase
{
    private function parseMoneyAsDecimal($input, string $currency = 'EUR')
    {
        static $moneyParser;

        if (!$moneyParser) {
            $currencies = new ISOCurrencies();
            $moneyParser = new DecimalMoneyParser($currencies);
        }

        return $moneyParser->parse((string) $input, $currency);
    }

    public function provideTransformations()
    {
        return [
            [null, ''],
            [1, 'EUR 1.00'],
            [1.5, 'EUR 1.50'],
            [1234.5, 'EUR 1234.50'],
            [12345.912, 'EUR 12345.91'],
            [1234.5, 'EUR 1234.50'],
        ];
    }

    /**
     * @dataProvider provideTransformations
     */
    public function testTransform($from, $to)
    {
        $transformer = new MoneyToStringTransformer('EUR');

        if (null !== $from) {
            $from = new MoneyValue($this->parseMoneyAsDecimal($from));
        }

        self::assertEquals($to, $transformer->transform($from));
    }

    /**
     * @dataProvider provideTransformations
     */
    public function testTransformWithoutCurrency($from, $to)
    {
        $transformer = new MoneyToStringTransformer('EUR');

        if (null !== $from) {
            $from = new MoneyValue($this->parseMoneyAsDecimal($from), false);
        }

        $to = mb_substr($to, 4);

        self::assertEquals($to, $transformer->transform($from));
    }

    /**
     * @dataProvider provideTransformations
     */
    public function testReverseTransform($to, $from)
    {
        $transformer = new MoneyToStringTransformer('EUR');

        if (null !== $to) {
            $to = new MoneyValue($this->parseMoneyAsDecimal($to));
        }

        self::assertEquals($to, $transformer->reverseTransform($from));
    }

    /**
     * @dataProvider provideTransformations
     */
    public function testReverseTransformWithoutCurrency($to, $from)
    {
        $transformer = new MoneyToStringTransformer('USD');

        if (null !== $to) {
            $to = new MoneyValue($this->parseMoneyAsDecimal($to, 'USD'), false);
            $from = mb_substr($from, 4);
        }

        self::assertEquals($to, $transformer->reverseTransform($from));
    }

    public function testTransformExpectsMoneyValue()
    {
        $transformer = new MoneyToStringTransformer('EUR');

        $this->expectException(TransformationFailedException::class);

        $transformer->transform('foo');
    }

    public function testReverseTransformExpectsString()
    {
        $transformer = new MoneyToStringTransformer('EUR');

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform(1);
    }

    public function testReverseTransformExpectsValidNumber()
    {
        $transformer = new MoneyToStringTransformer('EUR');

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('foo');
    }

    public function testReverseTransformDisallowsNaN()
    {
        $transformer = new MoneyToStringTransformer('EUR');

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('NaN');
    }

    public function testReverseTransformDisallowsNaN2()
    {
        $transformer = new MoneyToStringTransformer('EUR');

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('nan');
    }

    public function testReverseTransformDisallowsInfinity()
    {
        $transformer = new MoneyToStringTransformer('EUR');

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('∞');
    }

    public function testReverseTransformDisallowsInfinity2()
    {
        $transformer = new MoneyToStringTransformer('EUR');

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('∞,123');
    }

    public function testReverseTransformDisallowsNegativeInfinity()
    {
        $transformer = new MoneyToStringTransformer('EUR');

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('-∞');
    }

    public function testReverseTransformExpectsValidNumberOrCurrencyWithNumber()
    {
        $transformer = new MoneyToStringTransformer('EUR');

        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Value does not contain a valid 3 character currency code, got "fool".');

        $transformer->reverseTransform('fool 12.00');
    }

    public function testReverseTransformExpectsValidNumberOrCurrencyWithNumber2()
    {
        $transformer = new MoneyToStringTransformer('EUR');

        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Value does not contain a valid 3 character currency code, got "fo".');

        $transformer->reverseTransform('fo ol 12.00');
    }

    public function testReverseTransformExpectsCurrencyWithNumber()
    {
        $transformer = new MoneyToStringTransformer('EUR');

        $this->expectException(TransformationFailedException::class);
        $transformer->reverseTransform('foo 12.00');
    }
}
