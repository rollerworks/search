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
    protected function setUp()
    {
        parent::setUp();

        \Locale::setDefault('en');
    }

    private function parseMoneyAsDecimal($input, string $currency = 'EUR')
    {
        static $moneyParser;

        if (!$moneyParser) {
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
     */
    public function testTransform($from, $to, $locale)
    {
        // Since we test against other locales, we need the full implementation
        IntlTestHelper::requireFullIntl($this, false);

        \Locale::setDefault($locale);

        $transformer = new MoneyToLocalizedStringTransformer('EUR');

        if (null !== $from) {
            $from = new MoneyValue($this->parseMoneyAsDecimal($from));
        }

        self::assertEquals($to, $transformer->transform($from));
    }

    /**
     * @dataProvider provideTransformations
     */
    public function testTransformWithoutCurrency($from, $to, $locale)
    {
        // Since we test against other locales, we need the full implementation
        IntlTestHelper::requireFullIntl($this, false);

        \Locale::setDefault($locale);

        $transformer = new MoneyToLocalizedStringTransformer('EUR');

        if (null !== $from) {
            $from = new MoneyValue($this->parseMoneyAsDecimal($from), false);
        }

        $to = preg_replace('#(\s?\p{Sc}\s?)#u', '', $to);

        self::assertEquals($to, $transformer->transform($from));
    }

    public function testTransformWithoutCurrencyAndDifferentDefaultCurrency()
    {
        // Since we test against other locales, we need the full implementation
        IntlTestHelper::requireFullIntl($this, false);

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
     */
    public function testTransformWithGrouping($from, $to, $locale)
    {
        // Since we test against other locales, we need the full implementation
        IntlTestHelper::requireFullIntl($this, false);

        \Locale::setDefault($locale);

        $transformer = new MoneyToLocalizedStringTransformer('EUR', true);

        $from = new MoneyValue($this->parseMoneyAsDecimal($from));
        self::assertEquals($to, $transformer->transform($from));
    }

    /**
     * @dataProvider provideTransformations
     */
    public function testReverseTransform($to, $from, $locale)
    {
        // Since we test against other locales, we need the full implementation
        IntlTestHelper::requireFullIntl($this, '58.1');

        \Locale::setDefault($locale);

        $transformer = new MoneyToLocalizedStringTransformer('EUR');

        if (null !== $to) {
            $to = new MoneyValue($this->parseMoneyAsDecimal($to));
        }

        self::assertEquals($to, $transformer->reverseTransform($from));
    }

    /**
     * @dataProvider provideTransformations
     */
    public function testReverseTransformWithoutCurrency($to, $from, $locale)
    {
        // Since we test against other locales, we need the full implementation
        IntlTestHelper::requireFullIntl($this, false);

        \Locale::setDefault($locale);

        $from = preg_replace('#(\s?\p{Sc}\s?)#u', '', $from);
        $transformer = new MoneyToLocalizedStringTransformer('USD');

        if (null !== $to) {
            $to = new MoneyValue($this->parseMoneyAsDecimal($to, 'USD'), false);
        }

        self::assertEquals($to, $transformer->reverseTransform($from));
    }

    /**
     * @dataProvider provideTransformationsWithGrouping
     */
    public function testReverseTransformWithGrouping($to, $from, $locale)
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
     */
    public function testReverseTransformWithGroupingAndFixedSpaces()
    {
        // Since we test against other locales, we need the full implementation
        IntlTestHelper::requireFullIntl($this, false);

        \Locale::setDefault('ru');

        $transformer = new MoneyToLocalizedStringTransformer('EUR', true);

        self::assertEquals(
            new MoneyValue($this->parseMoneyAsDecimal(1234.5), false),
            $transformer->reverseTransform("1\xc2\xa0234,5")
        );
    }

    public function testReverseTransformWithGroupingButWithoutGroupSeparator()
    {
        // Since we test against "de_AT", we need the full implementation
        IntlTestHelper::requireFullIntl($this, false);

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

    public function testDecimalSeparatorMayBeDotIfGroupingSeparatorIsNotDot()
    {
        // Since we test against other locales, we need the full implementation
        IntlTestHelper::requireFullIntl($this, false);

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

    public function testDecimalSeparatorMayNotBeDotIfGroupingSeparatorIsDot()
    {
        // Since we test against "de_DE", we need the full implementation
        IntlTestHelper::requireFullIntl($this, '58.1');

        \Locale::setDefault('de_DE');

        $transformer = new MoneyToLocalizedStringTransformer('EUR', true);

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('1.234.5');
    }

    public function testDecimalSeparatorMayNotBeDotIfGroupingSeparatorIsDotWithNoGroupSep()
    {
        // Since we test against "de_DE", we need the full implementation
        IntlTestHelper::requireFullIntl($this, '58.1');

        \Locale::setDefault('de_DE');

        $transformer = new MoneyToLocalizedStringTransformer('EUR', true);

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('1234.5');
    }

    public function testDecimalSeparatorMayBeDotIfGroupingSeparatorIsDotButNoGroupingUsed()
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

    public function testDecimalSeparatorMayBeCommaIfGroupingSeparatorIsNotComma()
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

    public function testDecimalSeparatorMayNotBeCommaIfGroupingSeparatorIsComma()
    {
        $transformer = new MoneyToLocalizedStringTransformer('EUR', true);

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('1,234,5');
    }

    public function testDecimalSeparatorMayNotBeCommaIfGroupingSeparatorIsCommaWithNoGroupSep()
    {
        $transformer = new MoneyToLocalizedStringTransformer('EUR', true);

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('1234,5');
    }

    public function testDecimalSeparatorMayBeCommaIfGroupingSeparatorIsCommaButNoGroupingUsed()
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

    public function testTransformExpectsMoneyValue()
    {
        $transformer = new MoneyToLocalizedStringTransformer('EUR');

        $this->expectException(TransformationFailedException::class);

        $transformer->transform('foo');
    }

    public function testReverseTransformExpectsString()
    {
        $transformer = new MoneyToLocalizedStringTransformer('EUR');

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform(1);
    }

    public function testReverseTransformExpectsValidNumber()
    {
        $transformer = new MoneyToLocalizedStringTransformer('EUR');

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('foo');
    }

    public function testReverseTransformDisallowsNaN()
    {
        $transformer = new MoneyToLocalizedStringTransformer('EUR');

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('NaN');
    }

    public function testReverseTransformDisallowsNaN2()
    {
        $transformer = new MoneyToLocalizedStringTransformer('EUR');

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('nan');
    }

    public function testReverseTransformDisallowsInfinity()
    {
        $transformer = new MoneyToLocalizedStringTransformer('EUR');

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('∞');
    }

    public function testReverseTransformDisallowsInfinity2()
    {
        $transformer = new MoneyToLocalizedStringTransformer('EUR');

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('∞,123');
    }

    public function testReverseTransformDisallowsNegativeInfinity()
    {
        $transformer = new MoneyToLocalizedStringTransformer('EUR');

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('-∞');
    }
}
