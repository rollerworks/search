<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
        $this->factory->createField('money', 'money');
    }

    public function testPassMoneyNL()
    {
        \Locale::setDefault('nl_NL');

        $field = $this->factory->createField('money', 'money');

        $this->assertTransformedEquals($field, new MoneyValue('EUR', '12.00'), '€ 12,00');
        $this->assertTransformedEquals($field, new MoneyValue('EUR', '12.00'), '12,00');
    }

    public function testPassMoneyDe()
    {
        \Locale::setDefault('de_DE');

        $field = $this->factory->createField('money', 'money');

        $this->assertTransformedEquals($field, new MoneyValue('EUR', '12.00'), '12,00 €');
        $this->assertTransformedEquals($field, new MoneyValue('EUR', '12.00'), '12,00');
    }

    public function testMoneyPatternWorksForYen()
    {
        \Locale::setDefault('en_US');

        $field = $this->factory->createField('money', 'money', array('default_currency' => 'JPY'));

        $this->assertTransformedEquals($field, new MoneyValue('JPY', '12.00'), '¥12');
        $this->assertTransformedEquals($field, new MoneyValue('JPY', '12.00'), '12.00');
        $this->assertTransformedEquals($field, new MoneyValue('EUR', '12.00'), '€12.00');
    }

    protected function getTestedType()
    {
        return 'money';
    }
}
