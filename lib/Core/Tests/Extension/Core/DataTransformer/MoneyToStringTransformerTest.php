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
use Money\Currency;
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

        if (! $moneyParser) {
            $currencies = new ISOCurrencies();
            $moneyParser = new DecimalMoneyParser($currencies);
        }

        return $moneyParser->parse((string) $input, new Currency($currency));
    }

    public static function provideTransformations(): iterable
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
     *
     * @test
     */
    public function transform($from, $to): void
    {
        $transformer = new MoneyToStringTransformer('EUR');

        if ($from !== null) {
            $from = new MoneyValue($this->parseMoneyAsDecimal($from));
        }

        self::assertEquals($to, $transformer->transform($from));
    }

    /**
     * @dataProvider provideTransformations
     *
     * @test
     */
    public function transform_without_currency($from, $to): void
    {
        $transformer = new MoneyToStringTransformer('EUR');

        if ($from !== null) {
            $from = new MoneyValue($this->parseMoneyAsDecimal($from), false);
        }

        $to = mb_substr($to, 4);

        self::assertEquals($to, $transformer->transform($from));
    }

    /**
     * @dataProvider provideTransformations
     *
     * @test
     */
    public function reverse_transform($to, $from): void
    {
        $transformer = new MoneyToStringTransformer('EUR');

        if ($to !== null) {
            $to = new MoneyValue($this->parseMoneyAsDecimal($to));
        }

        self::assertEquals($to, $transformer->reverseTransform($from));
    }

    /**
     * @dataProvider provideTransformations
     *
     * @test
     */
    public function reverse_transform_without_currency($to, $from): void
    {
        $transformer = new MoneyToStringTransformer('USD');

        if ($to !== null) {
            $to = new MoneyValue($this->parseMoneyAsDecimal($to, 'USD'), false);
            $from = mb_substr($from, 4);
        }

        self::assertEquals($to, $transformer->reverseTransform($from));
    }

    /** @test */
    public function transform_expects_money_value(): void
    {
        $transformer = new MoneyToStringTransformer('EUR');

        $this->expectException(TransformationFailedException::class);

        $transformer->transform('foo');
    }

    /** @test */
    public function reverse_transform_expects_string(): void
    {
        $transformer = new MoneyToStringTransformer('EUR');

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform(1);
    }

    /** @test */
    public function reverse_transform_expects_valid_number(): void
    {
        $transformer = new MoneyToStringTransformer('EUR');

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('foo');
    }

    /** @test */
    public function reverse_transform_disallows_na_n(): void
    {
        $transformer = new MoneyToStringTransformer('EUR');

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('NaN');
    }

    /** @test */
    public function reverse_transform_disallows_na_n2(): void
    {
        $transformer = new MoneyToStringTransformer('EUR');

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('nan');
    }

    /** @test */
    public function reverse_transform_disallows_infinity(): void
    {
        $transformer = new MoneyToStringTransformer('EUR');

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('∞');
    }

    /** @test */
    public function reverse_transform_disallows_infinity2(): void
    {
        $transformer = new MoneyToStringTransformer('EUR');

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('∞,123');
    }

    /** @test */
    public function reverse_transform_disallows_negative_infinity(): void
    {
        $transformer = new MoneyToStringTransformer('EUR');

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('-∞');
    }

    /** @test */
    public function reverse_transform_expects_valid_number_or_currency_with_number(): void
    {
        $transformer = new MoneyToStringTransformer('EUR');

        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Value does not contain a valid 3 character currency code, got "fool".');

        $transformer->reverseTransform('fool 12.00');
    }

    /** @test */
    public function reverse_transform_expects_valid_number_or_currency_with_number2(): void
    {
        $transformer = new MoneyToStringTransformer('EUR');

        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Value does not contain a valid 3 character currency code, got "fo".');

        $transformer->reverseTransform('fo ol 12.00');
    }

    /** @test */
    public function reverse_transform_expects_currency_with_number(): void
    {
        $transformer = new MoneyToStringTransformer('EUR');

        $this->expectException(TransformationFailedException::class);
        $transformer->reverseTransform('foo 12.00');
    }
}
