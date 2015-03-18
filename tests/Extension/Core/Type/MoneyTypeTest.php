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
use Rollerworks\Component\Search\Test\FieldTypeTestCase;
use Symfony\Component\Intl\Util\IntlTestHelper;

class MoneyTypeTest extends FieldTypeTestCase
{
    protected function setUp()
    {
        // we test against different locales, so we need the full
        // implementation
        IntlTestHelper::requireFullIntl($this);

        parent::setUp();
    }

    public function testCreate()
    {
        $this->getFactory()->createField('money', 'money');
    }

    public function testPassMoneyNL()
    {
        \Locale::setDefault('nl_NL');

        $field = $this->getFactory()->createField('money', 'money');

        $this->assertTransformedEquals($field, new MoneyValue('EUR', '12.00'), '€ 12,00');
        $this->assertTransformedEquals($field, new MoneyValue('EUR', '12.00'), '12,00');
    }

    public function testPassMoneyDe()
    {
        \Locale::setDefault('de_DE');

        $field = $this->getFactory()->createField('money', 'money');

        $this->assertTransformedEquals($field, new MoneyValue('EUR', '12.00'), '12,00 €');
        $this->assertTransformedEquals($field, new MoneyValue('EUR', '12.00'), '12,00');
    }

    public function testMoneyPatternWorksForYen()
    {
        \Locale::setDefault('en_US');

        $field = $this->getFactory()->createField('money', 'money', array('default_currency' => 'JPY'));

        $this->assertTransformedEquals($field, new MoneyValue('JPY', '12.00'), '¥12');
        $this->assertTransformedEquals($field, new MoneyValue('JPY', '12.00'), '12.00');
        $this->assertTransformedEquals($field, new MoneyValue('EUR', '12.00'), '€12.00');
    }

    public function testViewIsConfiguredProperly()
    {
        $field = $this->getFactory()->createField(
            'money',
            'money',
            array(
                'precision' => 2,
                'grouping' => false,
                'divisor' => 1,
                'default_currency' => 'EUR',
            )
        );

        $field->setDataLocked();
        $fieldView = $field->createView();

        $this->assertArrayHasKey('precision', $fieldView->vars);
        $this->assertArrayHasKey('grouping', $fieldView->vars);
        $this->assertArrayHasKey('divisor', $fieldView->vars);
        $this->assertArrayHasKey('default_currency', $fieldView->vars);

        $this->assertEquals(2, $fieldView->vars['precision']);
        $this->assertFalse($fieldView->vars['grouping']);
        $this->assertEquals(1, $fieldView->vars['divisor']);
        $this->assertEquals('EUR', $fieldView->vars['default_currency']);
    }

    protected function getTestedType()
    {
        return 'money';
    }
}
