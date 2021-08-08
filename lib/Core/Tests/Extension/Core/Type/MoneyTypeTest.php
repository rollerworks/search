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

namespace Rollerworks\Component\Search\Tests\Extension\Core\Type;

use Money\Money;
use Rollerworks\Component\Search\Extension\Core\Model\MoneyValue;
use Rollerworks\Component\Search\Extension\Core\Type\MoneyType;
use Rollerworks\Component\Search\FieldSetView;
use Rollerworks\Component\Search\Input\NormStringQueryInput;
use Rollerworks\Component\Search\Input\ProcessorConfig;
use Rollerworks\Component\Search\Input\StringQueryInput;
use Rollerworks\Component\Search\SearchConditionBuilder;
use Rollerworks\Component\Search\Test\FieldTransformationAssertion;
use Rollerworks\Component\Search\Test\SearchIntegrationTestCase;
use Symfony\Component\Intl\Util\IntlTestHelper;

/**
 * @internal
 */
final class MoneyTypeTest extends SearchIntegrationTestCase
{
    protected function setUp(): void
    {
        // we test against different locales, so we need the full
        // implementation
        IntlTestHelper::requireFullIntl($this, '58.1');

        parent::setUp();
    }

    /** @test */
    public function pass_money_nl(): void
    {
        \Locale::setDefault('nl_NL');

        $field = $this->getFactory()->createField('money', MoneyType::class);

        FieldTransformationAssertion::assertThat($field)
            ->withInput('€ 12,20', 'EUR 12.20')
            ->successfullyTransformsTo(new MoneyValue(Money::EUR('1220')))
            ->andReverseTransformsTo('€ 12,20', 'EUR 12.20');

        FieldTransformationAssertion::assertThat($field)
            ->withInput('12,00', '12.00')
            ->successfullyTransformsTo(new MoneyValue(Money::EUR('1200'), false))
            ->andReverseTransformsTo('12,00', '12.00');
    }

    /** @test */
    public function pass_money_de(): void
    {
        \Locale::setDefault('de_DE');

        $field = $this->getFactory()->createField('money', MoneyType::class);

        FieldTransformationAssertion::assertThat($field)
            ->withInput('12,00 €', 'EUR 12.00')
            ->successfullyTransformsTo(new MoneyValue(Money::EUR('1200')))
            ->andReverseTransformsTo('12,00 €', 'EUR 12.00');

        FieldTransformationAssertion::assertThat($field)
            ->withInput('12,00', '12.00')
            ->successfullyTransformsTo(new MoneyValue(Money::EUR('1200'), false))
            ->andReverseTransformsTo('12,00', '12.00');
    }

    /** @test */
    public function money_pattern_works_for_yen(): void
    {
        \Locale::setDefault('en_US');

        $field = $this->getFactory()->createField('money', MoneyType::class, ['default_currency' => 'JPY']);

        FieldTransformationAssertion::assertThat($field)
            ->withInput('¥12,00', 'JPY 12.00')
            ->successfullyTransformsTo(new MoneyValue(Money::JPY('12')))
            ->andReverseTransformsTo('¥12', 'JPY 12');

        FieldTransformationAssertion::assertThat($field)
            ->withInput('¥12', 'JPY 12')
            ->successfullyTransformsTo(new MoneyValue(Money::JPY('12')))
            ->andReverseTransformsTo('¥12', 'JPY 12');

        FieldTransformationAssertion::assertThat($field)
            ->withInput('12', '12.00')
            ->successfullyTransformsTo(new MoneyValue(Money::JPY('12'), false))
            ->andReverseTransformsTo('12', '12');

        FieldTransformationAssertion::assertThat($field)
            ->withInput('€12.00', 'EUR 12.00')
            ->successfullyTransformsTo(new MoneyValue(Money::EUR('1200')))
            ->andReverseTransformsTo('€12.00', 'EUR 12.00');
    }

    /** @test */
    public function view_is_configured_properly(): void
    {
        $field = $this->getFactory()->createField('money', MoneyType::class, [
            'grouping' => false,
            'default_currency' => 'EUR',
        ]);

        $field->finalizeConfig();
        $fieldView = $field->createView(new FieldSetView());

        self::assertArrayHasKey('grouping', $fieldView->vars);
        self::assertArrayHasKey('default_currency', $fieldView->vars);

        self::assertFalse($fieldView->vars['grouping']);
        self::assertEquals('EUR', $fieldView->vars['default_currency']);
    }

    /** @test */
    public function value_lexing(): void
    {
        $field = $this->getFactory()->createField('money', MoneyType::class);
        $field->setNormTransformer(null);
        $field->setViewTransformer(null);

        $fieldSet = $this->getFactory()->createFieldSetBuilder()
            ->set($field)
            ->getFieldSet();

        $condition = SearchConditionBuilder::create($fieldSet)
            ->field('money')
                ->addSimpleValue('12.00 €')
                ->addSimpleValue('€ 12.00')
                ->addSimpleValue('€ 12.00')
                ->addSimpleValue('€ 12000.00')
                ->addSimpleValue('€ 12000.00')
            ->end()
            ->getSearchCondition();

        $this->assertConditionEquals(
            'money: 12.00 €, € 12.00, € 12.00, € 12000.00, "€ 12000.00"',
            $condition,
            new StringQueryInput(),
            new ProcessorConfig($fieldSet)
        );

        $condition2 = SearchConditionBuilder::create($fieldSet)
            ->field('money')
                ->addSimpleValue('EUR 12.00')
                ->addSimpleValue('EUR 12.00')
                ->addSimpleValue('12.00')
            ->end()
            ->getSearchCondition();

        $this->assertConditionEquals(
            'money: EUR 12.00, "EUR 12.00", 12.00',
            $condition2,
            new NormStringQueryInput(),
            new ProcessorConfig($fieldSet)
        );
    }
}
