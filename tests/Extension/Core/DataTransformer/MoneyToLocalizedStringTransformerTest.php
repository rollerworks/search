<?php

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
use Rollerworks\Component\Search\Extension\Core\DataTransformer\MoneyToLocalizedStringTransformer;
use Rollerworks\Component\Search\Extension\Core\Model\MoneyValue;

class MoneyToLocalizedStringTransformerTest extends TestCase
{
    /** @var MoneyToLocalizedStringTransformer */
    private $transformer;

    protected function setUp()
    {
        $this->transformer = new MoneyToLocalizedStringTransformer(2, false, null, 1, 'EUR');
    }

    /** @test */
    public function it_should_reverse_transform_to_a_MoneyObject()
    {
        \Locale::setDefault('de_AT');

        $value = new MoneyValue('EUR', '1.23');

        self::assertEquals('€ 1,23', $this->transformer->transform($value));
        self::assertEquals(new MoneyValue('EUR', '1.23'), $this->transformer->reverseTransform('€ 1,23'));
    }

    /** @test */
    public function it_should_reverse_transform_without_currency_to_a_MoneyObject()
    {
        \Locale::setDefault('de_AT');

        self::assertEquals(new MoneyValue('EUR', '1.23'), $this->transformer->reverseTransform('1,23'));
    }
}
