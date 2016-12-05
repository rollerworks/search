<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests\Extension\Core\Type;

use Rollerworks\Component\Search\Extension\Core\Model\MoneyValue;
use Rollerworks\Component\Search\Extension\Core\Type\MoneyType;
use Rollerworks\Component\Search\Test\FieldTransformationAssertion;
use Rollerworks\Component\Search\Test\SearchIntegrationTestCase;
use Symfony\Component\Intl\Util\IntlTestHelper;

class MoneyTypeTest extends SearchIntegrationTestCase
{
    protected function setUp()
    {
        // we test against different locales, so we need the full
        // implementation
        // IntlTestHelper::requireFullIntl($this);

        parent::setUp();
    }

    public function testPassMoneyNL()
    {
        \Locale::setDefault('nl_NL');

        $field = $this->getFactory()->createField('money', MoneyType::class);

        FieldTransformationAssertion::assertThat($field)
            ->withInput('€ 12,00', '€12.00')
            ->successfullyTransformsTo(new MoneyValue('EUR', '12.00'))
            ->andReverseTransformsTo('€ 12,00', '€12.00');

        FieldTransformationAssertion::assertThat($field)
            ->withInput('12,00')
            ->successfullyTransformsTo(new MoneyValue('EUR', '12.00'))
            ->andReverseTransformsTo('€ 12,00', '€12.00');
    }

    public function testPassMoneyDe()
    {
        \Locale::setDefault('de_DE');

        $field = $this->getFactory()->createField('money', MoneyType::class);

        FieldTransformationAssertion::assertThat($field)
            ->withInput('12,00 €', '€12.00')
            ->successfullyTransformsTo(new MoneyValue('EUR', '12.00'))
            ->andReverseTransformsTo('12,00 €', '€12.00');

        FieldTransformationAssertion::assertThat($field)
            ->withInput('12,00', '12.00')
            ->successfullyTransformsTo(new MoneyValue('EUR', '12.00'))
            ->andReverseTransformsTo('12,00 €', '€12.00');
    }

    public function testMoneyPatternWorksForYen()
    {
        \Locale::setDefault('en_US');

        $field = $this->getFactory()->createField('money', MoneyType::class, ['default_currency' => 'JPY']);

        FieldTransformationAssertion::assertThat($field)
            ->withInput('¥12,00')
            ->successfullyTransformsTo(new MoneyValue('JPY', '12.00'))
            ->andReverseTransformsTo('¥12', '¥12');

        FieldTransformationAssertion::assertThat($field)
            ->withInput('¥12')
            ->successfullyTransformsTo(new MoneyValue('JPY', '12.00'))
            ->andReverseTransformsTo('¥12', '¥12');

        FieldTransformationAssertion::assertThat($field)
            ->withInput('12')
            ->successfullyTransformsTo(new MoneyValue('JPY', '12.00'))
            ->andReverseTransformsTo('¥12', '¥12');

        FieldTransformationAssertion::assertThat($field)
            ->withInput('€12.00')
            ->successfullyTransformsTo(new MoneyValue('EUR', '12.00'))
            ->andReverseTransformsTo('€12.00', '€12.00');
    }

    public function testViewIsConfiguredProperly()
    {
        $field = $this->getFactory()->createField('money', MoneyType::class, [
            'precision' => 2,
            'grouping' => false,
            'divisor' => 1,
            'default_currency' => 'EUR',
        ]);

        $field->setDataLocked();
        $fieldView = $field->createView();

        self::assertArrayHasKey('precision', $fieldView->vars);
        self::assertArrayHasKey('grouping', $fieldView->vars);
        self::assertArrayHasKey('divisor', $fieldView->vars);
        self::assertArrayHasKey('default_currency', $fieldView->vars);

        self::assertEquals(2, $fieldView->vars['precision']);
        self::assertFalse($fieldView->vars['grouping']);
        self::assertEquals(1, $fieldView->vars['divisor']);
        self::assertEquals('EUR', $fieldView->vars['default_currency']);
    }
}
