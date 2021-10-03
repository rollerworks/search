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
use Rollerworks\Component\Search\Extension\Core\DataTransformer\MoneyToLocalizedStringTransformer;
use Rollerworks\Component\Search\Extension\Core\Model\MoneyValue;
use Symfony\Component\Intl\Util\IntlTestHelper;

/**
 * @internal
 */
final class MoneyToLocalizedStringTransformerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        \Locale::setDefault('en');
    }

    private function parseMoneyAsDecimal($input, string $currency = 'EUR')
    {
        static $moneyParser;

        if (! $moneyParser) {
            $currencies = new ISOCurrencies();
            $moneyParser = new DecimalMoneyParser($currencies);
        }

        return $moneyParser->parse((string) $input, new Currency($currency));
    }

    public function provideTransformations()
    {
        return [
            [null, '', 'de_AT'],
            [1, '€ 1,00', 'de_AT'],
            [1.5, '€ 1,50', 'de_AT'],
            [1234.5, '€ 1234,50', 'de_AT'],
            [12345.912, '€ 12345,91', 'de_AT'],
            [1234.5, '1234,50 €', 'ru'],
            [1234.5, '1234,50 €', 'fi'],
        ];
    }

    /**
     * @dataProvider provideTransformations
     *
     * @test
     */
    public function transform($from, $to, $locale): void
    {
        // Since we test against other locales, we need the full implementation
        IntlTestHelper::requireFullIntl($this);

        \Locale::setDefault($locale);

        $transformer = new MoneyToLocalizedStringTransformer('EUR');

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
    public function transform_without_currency($from, $to, $locale): void
    {
        // Since we test against other locales, we need the full implementation
        IntlTestHelper::requireFullIntl($this);

        \Locale::setDefault($locale);

        $transformer = new MoneyToLocalizedStringTransformer('EUR');

        if ($from !== null) {
            $from = new MoneyValue($this->parseMoneyAsDecimal($from), false);
        }

        $to = preg_replace('#(\s?\p{Sc}\s?)#u', '', $to);

        self::assertEquals($to, $transformer->transform($from));
    }

    /** @test */
    public function transform_without_currency_and_different_default_currency(): void
    {
        // Since we test against other locales, we need the full implementation
        IntlTestHelper::requireFullIntl($this);

        \Locale::setDefault('de_DE');

        $transformer = new MoneyToLocalizedStringTransformer('EUR');

        $from = new MoneyValue($this->parseMoneyAsDecimal(1234.5, 'USD'), false);

        self::assertEquals('1234,50', $transformer->transform($from));
    }

    public function provideTransformationsWithGrouping()
    {
        return [
            [1234.5, '1.234,50 €', 'de_DE'],
            [12345.912, '12.345,91 €', 'de_DE'],
            [1234.5, '1 234,50 €', 'fr'],
            [1234.5, '1 234,50 €', 'ru'],
            [1234.5, '1 234,50 €', 'fi'],
        ];
    }

    /**
     * @dataProvider provideTransformationsWithGrouping
     *
     * @test
     */
    public function transform_with_grouping($from, $to, $locale): void
    {
        // Since we test against other locales, we need the full implementation
        IntlTestHelper::requireFullIntl($this);

        \Locale::setDefault($locale);

        $transformer = new MoneyToLocalizedStringTransformer('EUR', true);

        $from = new MoneyValue($this->parseMoneyAsDecimal($from));
        self::assertEquals($to, $transformer->transform($from));
    }

    /**
     * @dataProvider provideTransformations
     *
     * @test
     */
    public function reverse_transform($to, $from, $locale): void
    {
        // Since we test against other locales, we need the full implementation
        IntlTestHelper::requireFullIntl($this, '58.1');

        \Locale::setDefault($locale);

        $transformer = new MoneyToLocalizedStringTransformer('EUR');

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
    public function reverse_transform_without_currency($to, $from, $locale): void
    {
        // Since we test against other locales, we need the full implementation
        IntlTestHelper::requireFullIntl($this);

        \Locale::setDefault($locale);

        $from = preg_replace('#(\s?\p{Sc}\s?)#u', '', $from);
        $transformer = new MoneyToLocalizedStringTransformer('USD');

        if ($to !== null) {
            $to = new MoneyValue($this->parseMoneyAsDecimal($to, 'USD'), false);
        }

        self::assertEquals($to, $transformer->reverseTransform($from));
    }

    /**
     * @dataProvider provideTransformationsWithGrouping
     *
     * @test
     */
    public function reverse_transform_with_grouping($to, $from, $locale): void
    {
        // Since we test against other locales, we need the full implementation
        IntlTestHelper::requireFullIntl($this, '58.1');

        \Locale::setDefault($locale);

        $transformer = new MoneyToLocalizedStringTransformer('EUR', true);

        $to = new MoneyValue($this->parseMoneyAsDecimal($to));
        self::assertEquals($to, $transformer->reverseTransform($from));
    }

    /**
     * @see https://github.com/symfony/symfony/issues/7609
     *
     * @test
     */
    public function reverse_transform_with_grouping_and_fixed_spaces(): void
    {
        // Since we test against other locales, we need the full implementation
        IntlTestHelper::requireFullIntl($this);

        \Locale::setDefault('ru');

        $transformer = new MoneyToLocalizedStringTransformer('EUR', true);

        self::assertEquals(
            new MoneyValue($this->parseMoneyAsDecimal(1234.5), false),
            $transformer->reverseTransform("1\xc2\xa0234,5")
        );
    }

    /** @test */
    public function reverse_transform_with_grouping_but_without_group_separator(): void
    {
        // Since we test against "de_AT", we need the full implementation
        IntlTestHelper::requireFullIntl($this);

        \Locale::setDefault('de_AT');

        $transformer = new MoneyToLocalizedStringTransformer('EUR', true);

        // omit group separator
        self::assertEquals(
            new MoneyValue($this->parseMoneyAsDecimal(1234.5), false),
            $transformer->reverseTransform('1234,5')
        );

        self::assertEquals(
            new MoneyValue($this->parseMoneyAsDecimal(12345.912), false),
            $transformer->reverseTransform('12345,912')
        );
    }

    /** @test */
    public function decimal_separator_may_be_dot_if_grouping_separator_is_not_dot(): void
    {
        // Since we test against other locales, we need the full implementation
        IntlTestHelper::requireFullIntl($this);

        \Locale::setDefault('fr');
        $transformer = new MoneyToLocalizedStringTransformer('EUR', true);

        // completely valid format
        self::assertEquals(
            new MoneyValue($this->parseMoneyAsDecimal(1234.5), false),
            $transformer->reverseTransform('1 234,5')
        );

        // accept dots
        self::assertEquals(
            new MoneyValue($this->parseMoneyAsDecimal(1234.5), false),
            $transformer->reverseTransform('1 234.5')
        );

        // omit group separator
        self::assertEquals(
            new MoneyValue($this->parseMoneyAsDecimal(1234.5), false),
            $transformer->reverseTransform('1234,5')
        );

        self::assertEquals(
            new MoneyValue($this->parseMoneyAsDecimal(1234.5), false),
            $transformer->reverseTransform('1234.5')
        );
    }

    /** @test */
    public function decimal_separator_may_not_be_dot_if_grouping_separator_is_dot(): void
    {
        // Since we test against "de_DE", we need the full implementation
        IntlTestHelper::requireFullIntl($this, '58.1');

        \Locale::setDefault('de_DE');

        $transformer = new MoneyToLocalizedStringTransformer('EUR', true);

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('1.234.5');
    }

    /** @test */
    public function decimal_separator_may_not_be_dot_if_grouping_separator_is_dot_with_no_group_sep(): void
    {
        // Since we test against "de_DE", we need the full implementation
        IntlTestHelper::requireFullIntl($this, '58.1');

        \Locale::setDefault('de_DE');

        $transformer = new MoneyToLocalizedStringTransformer('EUR', true);

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('1234.5');
    }

    /** @test */
    public function decimal_separator_may_be_dot_if_grouping_separator_is_dot_but_no_grouping_used(): void
    {
        // Since we test against other locales, we need the full implementation
        IntlTestHelper::requireFullIntl($this, '58.1');

        \Locale::setDefault('fr');
        $transformer = new MoneyToLocalizedStringTransformer('EUR');

        self::assertEquals(
            new MoneyValue($this->parseMoneyAsDecimal(1234.5), false),
            $transformer->reverseTransform('1234,5')
        );

        self::assertEquals(
            new MoneyValue($this->parseMoneyAsDecimal(1234.5), false),
            $transformer->reverseTransform('1234.5')
        );
    }

    /** @test */
    public function decimal_separator_may_be_comma_if_grouping_separator_is_not_comma(): void
    {
        // Since we test against other locales, we need the full implementation
        IntlTestHelper::requireFullIntl($this, '58.1');

        \Locale::setDefault('bg');
        $transformer = new MoneyToLocalizedStringTransformer('EUR', true);

        // completely valid format
        self::assertEquals(
            new MoneyValue($this->parseMoneyAsDecimal(1234.5), false),
            $transformer->reverseTransform('1 234,5')
        );

        // accept commas
        self::assertEquals(
            new MoneyValue($this->parseMoneyAsDecimal(1234.5), false),
            $transformer->reverseTransform('1234.5')
        );

        // omit group separator
        self::assertEquals(
            new MoneyValue($this->parseMoneyAsDecimal(1234.5), false),
            $transformer->reverseTransform('1234.5')
        );

        self::assertEquals(
            new MoneyValue($this->parseMoneyAsDecimal(1234.5), false),
            $transformer->reverseTransform('1234,5')
        );
    }

    /** @test */
    public function decimal_separator_may_not_be_comma_if_grouping_separator_is_comma(): void
    {
        $transformer = new MoneyToLocalizedStringTransformer('EUR', true);

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('1,234,5');
    }

    /** @test */
    public function decimal_separator_may_not_be_comma_if_grouping_separator_is_comma_with_no_group_sep(): void
    {
        $transformer = new MoneyToLocalizedStringTransformer('EUR', true);

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('1234,5');
    }

    /** @test */
    public function decimal_separator_may_be_comma_if_grouping_separator_is_comma_but_no_grouping_used(): void
    {
        $transformer = new MoneyToLocalizedStringTransformer('EUR');

        self::assertEquals(
            new MoneyValue($this->parseMoneyAsDecimal(1234.5), false),
            $transformer->reverseTransform('1234,50')
        );

        self::assertEquals(
            new MoneyValue($this->parseMoneyAsDecimal(1234.5), false),
            $transformer->reverseTransform('1234.50')
        );
    }

    /** @test */
    public function transform_expects_money_value(): void
    {
        $transformer = new MoneyToLocalizedStringTransformer('EUR');

        $this->expectException(TransformationFailedException::class);

        $transformer->transform('foo');
    }

    /** @test */
    public function reverse_transform_expects_string(): void
    {
        $transformer = new MoneyToLocalizedStringTransformer('EUR');

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform(1);
    }

    /** @test */
    public function reverse_transform_expects_valid_number(): void
    {
        $transformer = new MoneyToLocalizedStringTransformer('EUR');

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('foo');
    }

    /** @test */
    public function reverse_transform_disallows_na_n(): void
    {
        $transformer = new MoneyToLocalizedStringTransformer('EUR');

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('NaN');
    }

    /** @test */
    public function reverse_transform_disallows_na_n2(): void
    {
        $transformer = new MoneyToLocalizedStringTransformer('EUR');

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('nan');
    }

    /** @test */
    public function reverse_transform_disallows_infinity(): void
    {
        $transformer = new MoneyToLocalizedStringTransformer('EUR');

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('∞');
    }

    /** @test */
    public function reverse_transform_disallows_infinity2(): void
    {
        $transformer = new MoneyToLocalizedStringTransformer('EUR');

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('∞,123');
    }

    /** @test */
    public function reverse_transform_disallows_negative_infinity(): void
    {
        $transformer = new MoneyToLocalizedStringTransformer('EUR');

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('-∞');
    }
}
