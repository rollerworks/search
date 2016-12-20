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

use PHPUnit\Framework\TestCase;
use Rollerworks\Component\Search\Exception\TransformationFailedException;
use Rollerworks\Component\Search\Extension\Core\DataTransformer\MoneyToLocalizedStringTransformer;
use Rollerworks\Component\Search\Extension\Core\Model\MoneyValue;
use Symfony\Component\Intl\Util\IntlTestHelper;

class MoneyToLocalizedStringTransformerTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();

        \Locale::setDefault('en');
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

        $transformer = new MoneyToLocalizedStringTransformer(null, null, null, null, 'EUR');

        if (null !== $from) {
            $from = new MoneyValue('EUR', $from);
        }

        $this->assertEquals($to, $transformer->transform($from));
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

        $transformer = new MoneyToLocalizedStringTransformer(null, true, null, null, 'EUR');

        $from = new MoneyValue('EUR', $from);
        $this->assertEquals($to, $transformer->transform($from));
    }

    public function testTransformWithScale()
    {
        // Since we test against "de_AT", we need the full implementation
        IntlTestHelper::requireFullIntl($this, '58.1');

        \Locale::setDefault('de_AT');

        $transformer = new MoneyToLocalizedStringTransformer(2, null, null, null, 'EUR');

        $this->assertEquals('€ 1234,50', $transformer->transform(new MoneyValue('EUR', 1234.5)));
        $this->assertEquals('€ 678,92', $transformer->transform(new MoneyValue('EUR', 678.916)));
    }

    public function transformWithRoundingProvider()
    {
        return [
            // towards positive infinity (1.6 -> 2, -1.6 -> -1)
            [0, 1234.5, '€ 1235', MoneyToLocalizedStringTransformer::ROUND_CEILING],
            [0, 1234.4, '€ 1235', MoneyToLocalizedStringTransformer::ROUND_CEILING],
            [0, -1234.5, '-€ 1234', MoneyToLocalizedStringTransformer::ROUND_CEILING],
            [0, -1234.4, '-€ 1234', MoneyToLocalizedStringTransformer::ROUND_CEILING],
            [1, 123.45, '€ 123,5', MoneyToLocalizedStringTransformer::ROUND_CEILING],
            [1, 123.44, '€ 123,5', MoneyToLocalizedStringTransformer::ROUND_CEILING],
            [1, -123.45, '-€ 123,4', MoneyToLocalizedStringTransformer::ROUND_CEILING],
            [1, -123.44, '-€ 123,4', MoneyToLocalizedStringTransformer::ROUND_CEILING],
            // towards negative infinity (1.6 -> 1, -1.6 -> -2)
            [0, 1234.5, '€ 1234', MoneyToLocalizedStringTransformer::ROUND_FLOOR],
            [0, 1234.4, '€ 1234', MoneyToLocalizedStringTransformer::ROUND_FLOOR],
            [0, -1234.5, '-€ 1235', MoneyToLocalizedStringTransformer::ROUND_FLOOR],
            [0, -1234.4, '-€ 1235', MoneyToLocalizedStringTransformer::ROUND_FLOOR],
            [1, 123.45, '€ 123,4', MoneyToLocalizedStringTransformer::ROUND_FLOOR],
            [1, 123.44, '€ 123,4', MoneyToLocalizedStringTransformer::ROUND_FLOOR],
            [1, -123.45, '-€ 123,5', MoneyToLocalizedStringTransformer::ROUND_FLOOR],
            [1, -123.44, '-€ 123,5', MoneyToLocalizedStringTransformer::ROUND_FLOOR],
            // away from zero (1.6 -> 2, -1.6 -> 2)
            [0, 1234.5, '€ 1235', MoneyToLocalizedStringTransformer::ROUND_UP],
            [0, 1234.4, '€ 1235', MoneyToLocalizedStringTransformer::ROUND_UP],
            [0, -1234.5, '-€ 1235', MoneyToLocalizedStringTransformer::ROUND_UP],
            [0, -1234.4, '-€ 1235', MoneyToLocalizedStringTransformer::ROUND_UP],
            [1, 123.45, '€ 123,5', MoneyToLocalizedStringTransformer::ROUND_UP],
            [1, 123.44, '€ 123,5', MoneyToLocalizedStringTransformer::ROUND_UP],
            [1, -123.45, '-€ 123,5', MoneyToLocalizedStringTransformer::ROUND_UP],
            [1, -123.44, '-€ 123,5', MoneyToLocalizedStringTransformer::ROUND_UP],
            // towards zero (1.6 -> 1, -1.6 -> -1)
            [0, 1234.5, '€ 1234', MoneyToLocalizedStringTransformer::ROUND_DOWN],
            [0, 1234.4, '€ 1234', MoneyToLocalizedStringTransformer::ROUND_DOWN],
            [0, -1234.5, '-€ 1234', MoneyToLocalizedStringTransformer::ROUND_DOWN],
            [0, -1234.4, '-€ 1234', MoneyToLocalizedStringTransformer::ROUND_DOWN],
            [1, 123.45, '€ 123,4', MoneyToLocalizedStringTransformer::ROUND_DOWN],
            [1, 123.44, '€ 123,4', MoneyToLocalizedStringTransformer::ROUND_DOWN],
            [1, -123.45, '-€ 123,4', MoneyToLocalizedStringTransformer::ROUND_DOWN],
            [1, -123.44, '-€ 123,4', MoneyToLocalizedStringTransformer::ROUND_DOWN],
            // round halves (.5) to the next even number
            [0, 1234.6, '€ 1235', MoneyToLocalizedStringTransformer::ROUND_HALF_EVEN],
            [0, 1234.5, '€ 1234', MoneyToLocalizedStringTransformer::ROUND_HALF_EVEN],
            [0, 1234.4, '€ 1234', MoneyToLocalizedStringTransformer::ROUND_HALF_EVEN],
            [0, 1233.5, '€ 1234', MoneyToLocalizedStringTransformer::ROUND_HALF_EVEN],
            [0, 1232.5, '€ 1232', MoneyToLocalizedStringTransformer::ROUND_HALF_EVEN],
            [0, -1234.6, '-€ 1235', MoneyToLocalizedStringTransformer::ROUND_HALF_EVEN],
            [0, -1234.5, '-€ 1234', MoneyToLocalizedStringTransformer::ROUND_HALF_EVEN],
            [0, -1234.4, '-€ 1234', MoneyToLocalizedStringTransformer::ROUND_HALF_EVEN],
            [0, -1233.5, '-€ 1234', MoneyToLocalizedStringTransformer::ROUND_HALF_EVEN],
            [0, -1232.5, '-€ 1232', MoneyToLocalizedStringTransformer::ROUND_HALF_EVEN],
            [1, 123.46, '€ 123,5', MoneyToLocalizedStringTransformer::ROUND_HALF_EVEN],
            [1, 123.45, '€ 123,4', MoneyToLocalizedStringTransformer::ROUND_HALF_EVEN],
            [1, 123.44, '€ 123,4', MoneyToLocalizedStringTransformer::ROUND_HALF_EVEN],
            [1, 123.35, '€ 123,4', MoneyToLocalizedStringTransformer::ROUND_HALF_EVEN],
            [1, 123.25, '€ 123,2', MoneyToLocalizedStringTransformer::ROUND_HALF_EVEN],
            [1, -123.46, '-€ 123,5', MoneyToLocalizedStringTransformer::ROUND_HALF_EVEN],
            [1, -123.45, '-€ 123,4', MoneyToLocalizedStringTransformer::ROUND_HALF_EVEN],
            [1, -123.44, '-€ 123,4', MoneyToLocalizedStringTransformer::ROUND_HALF_EVEN],
            [1, -123.35, '-€ 123,4', MoneyToLocalizedStringTransformer::ROUND_HALF_EVEN],
            [1, -123.25, '-€ 123,2', MoneyToLocalizedStringTransformer::ROUND_HALF_EVEN],
            // round halves (.5) away from zero
            [0, 1234.6, '€ 1235', MoneyToLocalizedStringTransformer::ROUND_HALF_UP],
            [0, 1234.5, '€ 1235', MoneyToLocalizedStringTransformer::ROUND_HALF_UP],
            [0, 1234.4, '€ 1234', MoneyToLocalizedStringTransformer::ROUND_HALF_UP],
            [0, -1234.6, '-€ 1235', MoneyToLocalizedStringTransformer::ROUND_HALF_UP],
            [0, -1234.5, '-€ 1235', MoneyToLocalizedStringTransformer::ROUND_HALF_UP],
            [0, -1234.4, '-€ 1234', MoneyToLocalizedStringTransformer::ROUND_HALF_UP],
            [1, 123.46, '€ 123,5', MoneyToLocalizedStringTransformer::ROUND_HALF_UP],
            [1, 123.45, '€ 123,5', MoneyToLocalizedStringTransformer::ROUND_HALF_UP],
            [1, 123.44, '€ 123,4', MoneyToLocalizedStringTransformer::ROUND_HALF_UP],
            [1, -123.46, '-€ 123,5', MoneyToLocalizedStringTransformer::ROUND_HALF_UP],
            [1, -123.45, '-€ 123,5', MoneyToLocalizedStringTransformer::ROUND_HALF_UP],
            [1, -123.44, '-€ 123,4', MoneyToLocalizedStringTransformer::ROUND_HALF_UP],
            // round halves (.5) towards zero
            [0, 1234.6, '€ 1235', MoneyToLocalizedStringTransformer::ROUND_HALF_DOWN],
            [0, 1234.5, '€ 1234', MoneyToLocalizedStringTransformer::ROUND_HALF_DOWN],
            [0, 1234.4, '€ 1234', MoneyToLocalizedStringTransformer::ROUND_HALF_DOWN],
            [0, -1234.6, '-€ 1235', MoneyToLocalizedStringTransformer::ROUND_HALF_DOWN],
            [0, -1234.5, '-€ 1234', MoneyToLocalizedStringTransformer::ROUND_HALF_DOWN],
            [0, -1234.4, '-€ 1234', MoneyToLocalizedStringTransformer::ROUND_HALF_DOWN],
            [1, 123.46, '€ 123,5', MoneyToLocalizedStringTransformer::ROUND_HALF_DOWN],
            [1, 123.45, '€ 123,4', MoneyToLocalizedStringTransformer::ROUND_HALF_DOWN],
            [1, 123.44, '€ 123,4', MoneyToLocalizedStringTransformer::ROUND_HALF_DOWN],
            [1, -123.46, '-€ 123,5', MoneyToLocalizedStringTransformer::ROUND_HALF_DOWN],
            [1, -123.45, '-€ 123,4', MoneyToLocalizedStringTransformer::ROUND_HALF_DOWN],
            [1, -123.44, '-€ 123,4', MoneyToLocalizedStringTransformer::ROUND_HALF_DOWN],
        ];
    }

    /**
     * @dataProvider transformWithRoundingProvider
     */
    public function testTransformWithRounding($scale, $input, $output, $roundingMode)
    {
        // Since we test against "de_AT", we need the full implementation
        IntlTestHelper::requireFullIntl($this, '58.1');

        \Locale::setDefault('de_AT');

        $transformer = new MoneyToLocalizedStringTransformer($scale, null, $roundingMode, null, 'EUR');

        $input = new MoneyValue('EUR', $input);
        $this->assertEquals($output, $transformer->transform($input));
    }

    public function testTransformDoesNotRoundIfNoScale()
    {
        // Since we test against "de_AT", we need the full implementation
        IntlTestHelper::requireFullIntl($this, '58.1');

        \Locale::setDefault('de_AT');

        $transformer = new MoneyToLocalizedStringTransformer(null, null, MoneyToLocalizedStringTransformer::ROUND_DOWN, null, 'EUR');

        $this->assertEquals('€ 1234,55', $transformer->transform(new MoneyValue('EUR', 1234.547)));
    }

    /**
     * @dataProvider provideTransformations
     */
    public function testReverseTransform($to, $from, $locale)
    {
        // Since we test against other locales, we need the full implementation
        IntlTestHelper::requireFullIntl($this, '58.1');

        \Locale::setDefault($locale);

        $transformer = new MoneyToLocalizedStringTransformer(null, null, null, null, 'EUR');

        if (null !== $to) {
            $to = new MoneyValue('EUR', (float) number_format($to, 2, '.', ''));
        }

        $this->assertEquals($to, $transformer->reverseTransform($from));
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
        $transformer = new MoneyToLocalizedStringTransformer(null, null, null, null, 'USD');

        if (null !== $to) {
            $to = new MoneyValue('USD', (float) number_format($to, 2, '.', ''));
        }

        $this->assertEquals($to, $transformer->reverseTransform($from));
    }

    /**
     * @dataProvider provideTransformationsWithGrouping
     */
    public function testReverseTransformWithGrouping($to, $from, $locale)
    {
        // Since we test against other locales, we need the full implementation
        IntlTestHelper::requireFullIntl($this, '58.1');

        \Locale::setDefault($locale);

        $transformer = new MoneyToLocalizedStringTransformer(null, true, null, null, 'EUR');

        $to = new MoneyValue('EUR', (float) number_format($to, 2, '.', ''));
        $this->assertEquals($to, $transformer->reverseTransform($from));
    }

    /**
     * @see https://github.com/symfony/symfony/issues/7609
     */
    public function testReverseTransformWithGroupingAndFixedSpaces()
    {
        // Since we test against other locales, we need the full implementation
        IntlTestHelper::requireFullIntl($this, false);

        \Locale::setDefault('ru');

        $transformer = new MoneyToLocalizedStringTransformer(null, true, null, null, 'EUR');

        $this->assertEquals(new MoneyValue('EUR', 1234.5), $transformer->reverseTransform("1\xc2\xa0234,5"));
    }

    public function testReverseTransformWithGroupingButWithoutGroupSeparator()
    {
        // Since we test against "de_AT", we need the full implementation
        IntlTestHelper::requireFullIntl($this, false);

        \Locale::setDefault('de_AT');

        $transformer = new MoneyToLocalizedStringTransformer(null, true, null, null, 'EUR');

        // omit group separator
        $this->assertEquals(new MoneyValue('EUR', 1234.5), $transformer->reverseTransform('1234,5'));
        $this->assertEquals(new MoneyValue('EUR', 12345.912), $transformer->reverseTransform('12345,912'));
    }

    public function reverseTransformWithRoundingProvider()
    {
        return [
            // towards positive infinity (1.6 -> 2, -1.6 -> -1)
            [0, '1234,5', 1235, MoneyToLocalizedStringTransformer::ROUND_CEILING],
            [0, '1234,4', 1235, MoneyToLocalizedStringTransformer::ROUND_CEILING],
            [0, '-1234,5', -1234, MoneyToLocalizedStringTransformer::ROUND_CEILING],
            [0, '-1234,4', -1234, MoneyToLocalizedStringTransformer::ROUND_CEILING],
            [1, '123,45', 123.5, MoneyToLocalizedStringTransformer::ROUND_CEILING],
            [1, '123,44', 123.5, MoneyToLocalizedStringTransformer::ROUND_CEILING],
            [1, '-123,45', -123.4, MoneyToLocalizedStringTransformer::ROUND_CEILING],
            [1, '-123,44', -123.4, MoneyToLocalizedStringTransformer::ROUND_CEILING],
            // towards negative infinity (1.6 -> 1, -1.6 -> -2)
            [0, '1234,5', 1234, MoneyToLocalizedStringTransformer::ROUND_FLOOR],
            [0, '1234,4', 1234, MoneyToLocalizedStringTransformer::ROUND_FLOOR],
            [0, '-1234,5', -1235, MoneyToLocalizedStringTransformer::ROUND_FLOOR],
            [0, '-1234,4', -1235, MoneyToLocalizedStringTransformer::ROUND_FLOOR],
            [1, '123,45', 123.4, MoneyToLocalizedStringTransformer::ROUND_FLOOR],
            [1, '123,44', 123.4, MoneyToLocalizedStringTransformer::ROUND_FLOOR],
            [1, '-123,45', -123.5, MoneyToLocalizedStringTransformer::ROUND_FLOOR],
            [1, '-123,44', -123.5, MoneyToLocalizedStringTransformer::ROUND_FLOOR],
            // away from zero (1.6 -> 2, -1.6 -> 2)
            [0, '1234,5', 1235, MoneyToLocalizedStringTransformer::ROUND_UP],
            [0, '1234,4', 1235, MoneyToLocalizedStringTransformer::ROUND_UP],
            [0, '-1234,5', -1235, MoneyToLocalizedStringTransformer::ROUND_UP],
            [0, '-1234,4', -1235, MoneyToLocalizedStringTransformer::ROUND_UP],
            [1, '123,45', 123.5, MoneyToLocalizedStringTransformer::ROUND_UP],
            [1, '123,44', 123.5, MoneyToLocalizedStringTransformer::ROUND_UP],
            [1, '-123,45', -123.5, MoneyToLocalizedStringTransformer::ROUND_UP],
            [1, '-123,44', -123.5, MoneyToLocalizedStringTransformer::ROUND_UP],
            // towards zero (1.6 -> 1, -1.6 -> -1)
            [0, '1234,5', 1234, MoneyToLocalizedStringTransformer::ROUND_DOWN],
            [0, '1234,4', 1234, MoneyToLocalizedStringTransformer::ROUND_DOWN],
            [0, '-1234,5', -1234, MoneyToLocalizedStringTransformer::ROUND_DOWN],
            [0, '-1234,4', -1234, MoneyToLocalizedStringTransformer::ROUND_DOWN],
            [1, '123,45', 123.4, MoneyToLocalizedStringTransformer::ROUND_DOWN],
            [1, '123,44', 123.4, MoneyToLocalizedStringTransformer::ROUND_DOWN],
            [1, '-123,45', -123.4, MoneyToLocalizedStringTransformer::ROUND_DOWN],
            [1, '-123,44', -123.4, MoneyToLocalizedStringTransformer::ROUND_DOWN],
            // round halves (.5) to the next even number
            [0, '1234,6', 1235, MoneyToLocalizedStringTransformer::ROUND_HALF_EVEN],
            [0, '1234,5', 1234, MoneyToLocalizedStringTransformer::ROUND_HALF_EVEN],
            [0, '1234,4', 1234, MoneyToLocalizedStringTransformer::ROUND_HALF_EVEN],
            [0, '1233,5', 1234, MoneyToLocalizedStringTransformer::ROUND_HALF_EVEN],
            [0, '1232,5', 1232, MoneyToLocalizedStringTransformer::ROUND_HALF_EVEN],
            [0, '-1234,6', -1235, MoneyToLocalizedStringTransformer::ROUND_HALF_EVEN],
            [0, '-1234,5', -1234, MoneyToLocalizedStringTransformer::ROUND_HALF_EVEN],
            [0, '-1234,4', -1234, MoneyToLocalizedStringTransformer::ROUND_HALF_EVEN],
            [0, '-1233,5', -1234, MoneyToLocalizedStringTransformer::ROUND_HALF_EVEN],
            [0, '-1232,5', -1232, MoneyToLocalizedStringTransformer::ROUND_HALF_EVEN],
            [1, '123,46', 123.5, MoneyToLocalizedStringTransformer::ROUND_HALF_EVEN],
            [1, '123,45', 123.4, MoneyToLocalizedStringTransformer::ROUND_HALF_EVEN],
            [1, '123,44', 123.4, MoneyToLocalizedStringTransformer::ROUND_HALF_EVEN],
            [1, '123,35', 123.4, MoneyToLocalizedStringTransformer::ROUND_HALF_EVEN],
            [1, '123,25', 123.2, MoneyToLocalizedStringTransformer::ROUND_HALF_EVEN],
            [1, '-123,46', -123.5, MoneyToLocalizedStringTransformer::ROUND_HALF_EVEN],
            [1, '-123,45', -123.4, MoneyToLocalizedStringTransformer::ROUND_HALF_EVEN],
            [1, '-123,44', -123.4, MoneyToLocalizedStringTransformer::ROUND_HALF_EVEN],
            [1, '-123,35', -123.4, MoneyToLocalizedStringTransformer::ROUND_HALF_EVEN],
            [1, '-123,25', -123.2, MoneyToLocalizedStringTransformer::ROUND_HALF_EVEN],
            // round halves (.5) away from zero
            [0, '1234,6', 1235, MoneyToLocalizedStringTransformer::ROUND_HALF_UP],
            [0, '1234,5', 1235, MoneyToLocalizedStringTransformer::ROUND_HALF_UP],
            [0, '1234,4', 1234, MoneyToLocalizedStringTransformer::ROUND_HALF_UP],
            [0, '-1234,6', -1235, MoneyToLocalizedStringTransformer::ROUND_HALF_UP],
            [0, '-1234,5', -1235, MoneyToLocalizedStringTransformer::ROUND_HALF_UP],
            [0, '-1234,4', -1234, MoneyToLocalizedStringTransformer::ROUND_HALF_UP],
            [1, '123,46', 123.5, MoneyToLocalizedStringTransformer::ROUND_HALF_UP],
            [1, '123,45', 123.5, MoneyToLocalizedStringTransformer::ROUND_HALF_UP],
            [1, '123,44', 123.4, MoneyToLocalizedStringTransformer::ROUND_HALF_UP],
            [1, '-123,46', -123.5, MoneyToLocalizedStringTransformer::ROUND_HALF_UP],
            [1, '-123,45', -123.5, MoneyToLocalizedStringTransformer::ROUND_HALF_UP],
            [1, '-123,44', -123.4, MoneyToLocalizedStringTransformer::ROUND_HALF_UP],
            // round halves (.5) towards zero
            [0, '1234,6', 1235, MoneyToLocalizedStringTransformer::ROUND_HALF_DOWN],
            [0, '1234,5', 1234, MoneyToLocalizedStringTransformer::ROUND_HALF_DOWN],
            [0, '1234,4', 1234, MoneyToLocalizedStringTransformer::ROUND_HALF_DOWN],
            [0, '-1234,6', -1235, MoneyToLocalizedStringTransformer::ROUND_HALF_DOWN],
            [0, '-1234,5', -1234, MoneyToLocalizedStringTransformer::ROUND_HALF_DOWN],
            [0, '-1234,4', -1234, MoneyToLocalizedStringTransformer::ROUND_HALF_DOWN],
            [1, '123,46', 123.5, MoneyToLocalizedStringTransformer::ROUND_HALF_DOWN],
            [1, '123,45', 123.4, MoneyToLocalizedStringTransformer::ROUND_HALF_DOWN],
            [1, '123,44', 123.4, MoneyToLocalizedStringTransformer::ROUND_HALF_DOWN],
            [1, '-123,46', -123.5, MoneyToLocalizedStringTransformer::ROUND_HALF_DOWN],
            [1, '-123,45', -123.4, MoneyToLocalizedStringTransformer::ROUND_HALF_DOWN],
            [1, '-123,44', -123.4, MoneyToLocalizedStringTransformer::ROUND_HALF_DOWN],
        ];
    }

    /**
     * @dataProvider reverseTransformWithRoundingProvider
     */
    public function testReverseTransformWithRounding($scale, $input, $output, $roundingMode)
    {
        $transformer = new MoneyToLocalizedStringTransformer($scale, null, $roundingMode, null, 'EUR');

        $this->assertEquals(new MoneyValue('EUR', $output), $transformer->reverseTransform($input));
    }

    public function testReverseTransformDoesNotRoundIfNoScale()
    {
        $transformer = new MoneyToLocalizedStringTransformer(null, null, MoneyToLocalizedStringTransformer::ROUND_DOWN, null, 'EUR');

        $this->assertEquals(new MoneyValue('EUR', 1234.547), $transformer->reverseTransform('1234,547'));
    }

    public function testDecimalSeparatorMayBeDotIfGroupingSeparatorIsNotDot()
    {
        // Since we test against other locales, we need the full implementation
        IntlTestHelper::requireFullIntl($this, false);

        \Locale::setDefault('fr');
        $transformer = new MoneyToLocalizedStringTransformer(null, true, null, null, 'EUR');

        // completely valid format
        $this->assertEquals(new MoneyValue('EUR', 1234.5), $transformer->reverseTransform('1 234,5'));
        // accept dots
        $this->assertEquals(new MoneyValue('EUR', 1234.5), $transformer->reverseTransform('1 234.5'));
        // omit group separator
        $this->assertEquals(new MoneyValue('EUR', 1234.5), $transformer->reverseTransform('1234,5'));
        $this->assertEquals(new MoneyValue('EUR', 1234.5), $transformer->reverseTransform('1234.5'));
    }

    public function testDecimalSeparatorMayNotBeDotIfGroupingSeparatorIsDot()
    {
        // Since we test against "de_DE", we need the full implementation
        IntlTestHelper::requireFullIntl($this, '58.1');

        \Locale::setDefault('de_DE');

        $transformer = new MoneyToLocalizedStringTransformer(null, true, null, null, 'EUR');

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('1.234.5');
    }

    public function testDecimalSeparatorMayNotBeDotIfGroupingSeparatorIsDotWithNoGroupSep()
    {
        // Since we test against "de_DE", we need the full implementation
        IntlTestHelper::requireFullIntl($this, '58.1');

        \Locale::setDefault('de_DE');

        $transformer = new MoneyToLocalizedStringTransformer(null, true, null, null, 'EUR');

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('1234.5');
    }

    public function testDecimalSeparatorMayBeDotIfGroupingSeparatorIsDotButNoGroupingUsed()
    {
        // Since we test against other locales, we need the full implementation
        IntlTestHelper::requireFullIntl($this, '58.1');

        \Locale::setDefault('fr');
        $transformer = new MoneyToLocalizedStringTransformer(null, null, null, null, 'EUR');

        $this->assertEquals(new MoneyValue('EUR', 1234.5), $transformer->reverseTransform('1234,5'));
        $this->assertEquals(new MoneyValue('EUR', 1234.5), $transformer->reverseTransform('1234.5'));
    }

    public function testDecimalSeparatorMayBeCommaIfGroupingSeparatorIsNotComma()
    {
        // Since we test against other locales, we need the full implementation
        IntlTestHelper::requireFullIntl($this, '58.1');

        \Locale::setDefault('bg');
        $transformer = new MoneyToLocalizedStringTransformer(null, true, null, null, 'EUR');

        // completely valid format
        $this->assertEquals(new MoneyValue('EUR', 1234.5), $transformer->reverseTransform('1 234.5'));
        // accept commas
        $this->assertEquals(new MoneyValue('EUR', 1234.5), $transformer->reverseTransform('1 234,5'));
        // omit group separator
        $this->assertEquals(new MoneyValue('EUR', 1234.5), $transformer->reverseTransform('1234.5'));
        $this->assertEquals(new MoneyValue('EUR', 1234.5), $transformer->reverseTransform('1234,5'));
    }

    public function testDecimalSeparatorMayNotBeCommaIfGroupingSeparatorIsComma()
    {
        $transformer = new MoneyToLocalizedStringTransformer(null, true, null, null, 'EUR');

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('1,234,5');
    }

    public function testDecimalSeparatorMayNotBeCommaIfGroupingSeparatorIsCommaWithNoGroupSep()
    {
        $transformer = new MoneyToLocalizedStringTransformer(null, true, null, null, 'EUR');

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('1234,5');
    }

    public function testDecimalSeparatorMayBeCommaIfGroupingSeparatorIsCommaButNoGroupingUsed()
    {
        $transformer = new MoneyToLocalizedStringTransformer(null, null, null, null, 'EUR');

        $this->assertEquals(new MoneyValue('EUR', 1234.5), $transformer->reverseTransform('1234,5'));
        $this->assertEquals(new MoneyValue('EUR', 1234.5), $transformer->reverseTransform('1234.5'));
    }

    public function testTransformExpectsNumeric()
    {
        $transformer = new MoneyToLocalizedStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->transform('foo');
    }

    public function testReverseTransformExpectsString()
    {
        $transformer = new MoneyToLocalizedStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform(1);
    }

    public function testReverseTransformExpectsValidNumber()
    {
        $transformer = new MoneyToLocalizedStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('foo');
    }

    /**
     * @see https://github.com/symfony/symfony/issues/3161
     */
    public function testReverseTransformDisallowsNaN()
    {
        $transformer = new MoneyToLocalizedStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('NaN');
    }

    public function testReverseTransformDisallowsNaN2()
    {
        $transformer = new MoneyToLocalizedStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('nan');
    }

    public function testReverseTransformDisallowsInfinity()
    {
        $transformer = new MoneyToLocalizedStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('∞');
    }

    public function testReverseTransformDisallowsInfinity2()
    {
        $transformer = new MoneyToLocalizedStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('∞,123');
    }

    public function testReverseTransformDisallowsNegativeInfinity()
    {
        $transformer = new MoneyToLocalizedStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('-∞');
    }

    public function testReverseTransformDisallowsLeadingExtraCharacters()
    {
        $transformer = new MoneyToLocalizedStringTransformer();

        $this->expectException(TransformationFailedException::class);

        $transformer->reverseTransform('foo123');
    }

    public function testReverseTransformDisallowsCenteredExtraCharacters()
    {
        $transformer = new MoneyToLocalizedStringTransformer();

        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('The number contains unrecognized characters: "foo3"');

        $transformer->reverseTransform('12foo3');
    }

    public function testReverseTransformDisallowsCenteredExtraCharactersMultibyte()
    {
        // Since we test against other locales, we need the full implementation
        IntlTestHelper::requireFullIntl($this, false);

        \Locale::setDefault('ru');

        $transformer = new MoneyToLocalizedStringTransformer(null, true);

        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('The number contains unrecognized characters: "foo8"');

        $transformer->reverseTransform("12\xc2\xa0345,67foo8");
    }

    public function testReverseTransformIgnoresTrailingSpacesInExceptionMessage()
    {
        // Since we test against other locales, we need the full implementation
        IntlTestHelper::requireFullIntl($this, false);

        \Locale::setDefault('ru');

        $transformer = new MoneyToLocalizedStringTransformer(null, true);

        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('The number contains unrecognized characters: "foo8"');

        $transformer->reverseTransform("12\xc2\xa0345,67foo8  \xc2\xa0\t");
    }

    public function testReverseTransformDisallowsTrailingExtraCharacters()
    {
        $transformer = new MoneyToLocalizedStringTransformer();

        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('The number contains unrecognized characters: "foo"');

        $transformer->reverseTransform('123foo');
    }

    public function testReverseTransformDisallowsTrailingExtraCharactersMultibyte()
    {
        // Since we test against other locales, we need the full implementation
        IntlTestHelper::requireFullIntl($this, false);

        \Locale::setDefault('ru');

        $transformer = new MoneyToLocalizedStringTransformer(null, true);

        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('The number contains unrecognized characters: "foo"');

        $transformer->reverseTransform("12\xc2\xa0345,678foo");
    }
}
