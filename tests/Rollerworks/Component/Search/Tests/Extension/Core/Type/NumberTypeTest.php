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

use Rollerworks\Component\Search\Extension\Core\Type\DateTimeType;
use Rollerworks\Component\Search\Test\FieldTypeTestCase;
use Symfony\Component\Intl\Util\IntlTestHelper;

class NumberTypeTest extends FieldTypeTestCase
{
    public function testCreate()
    {
        $this->factory->createField('integer', 'integer');
    }

    public function testCastsToInteger()
    {
        $field = $this->factory->createField('number', 'number');

        $this->assertTransformedEquals($field, '1.678', '1,678', '1,678');
        $this->assertTransformedEquals($field, '1', '1', '1');
        $this->assertTransformedEquals($field, '-1', '-1', '-1');
    }

    public function testWrongInputFails()
    {
        $field = $this->factory->createField('integer', 'integer');

        $this->assertTransformedFails($field, 'foo');
        $this->assertTransformedFails($field, '+1');
    }

    public function testDefaultFormatting()
    {
        $field = $this->factory->createField('number', 'number');

        $this->assertTransformedEquals($field, '12345.67890', '12345,67890', '12345,679');
        $this->assertTransformedEquals($field, '12345.679', '12345,679', '12345,679');
    }

    public function testDefaultFormattingWithGrouping()
    {
        $field = $this->factory->createField('number', 'number', array('grouping' => true));

        $this->assertTransformedEquals($field, '12345.679', '12.345,679', '12.345,679');
        $this->assertTransformedEquals($field, '12345.679', '12345,679', '12.345,679');
    }

    public function testDefaultFormattingWithPrecision()
    {
        $field = $this->factory->createField('number', 'number', array('precision' => 2));

        $this->assertTransformedEquals($field, '12345.68', '12345,67890', '12345,68');
        $this->assertTransformedEquals($field, '12345.67', '12345,67', '12345,67');
    }

    public function testDefaultFormattingWithRounding()
    {
        $field = $this->factory->createField(
            'number',
            'number',
            array('precision' => 0, 'rounding_mode' => \NumberFormatter::ROUND_UP)
        );

        $this->assertTransformedEquals($field, '12346', '12345,54321', '12346');
        $this->assertTransformedEquals($field, '12345', '12345', '12345');
    }

    protected function setUp()
    {
        parent::setUp();

        // we test against "de_DE", so we need the full implementation
        IntlTestHelper::requireFullIntl($this);

        \Locale::setDefault('de_DE');
    }

    protected function getTestedType()
    {
        return 'number';
    }
}
