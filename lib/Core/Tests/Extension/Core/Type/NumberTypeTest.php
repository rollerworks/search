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

use Rollerworks\Component\Search\Extension\Core\Type\NumberType;
use Rollerworks\Component\Search\FieldSetView;
use Rollerworks\Component\Search\Test\FieldTransformationAssertion;
use Rollerworks\Component\Search\Test\SearchIntegrationTestCase;
use Symfony\Component\Intl\Util\IntlTestHelper;

/**
 * @internal
 */
final class NumberTypeTest extends SearchIntegrationTestCase
{
    public function testCastsToInteger()
    {
        $field = $this->getFactory()->createField('number', NumberType::class);

        FieldTransformationAssertion::assertThat($field)
            ->withInput('1,678', '1.678')
            ->successfullyTransformsTo('1.678')
            ->andReverseTransformsTo('1,678', '1.678');

        FieldTransformationAssertion::assertThat($field)
            ->withInput('1', '1')
            ->successfullyTransformsTo('1')
            ->andReverseTransformsTo('1', '1');

        FieldTransformationAssertion::assertThat($field)
            ->withInput('01', '01')
            ->successfullyTransformsTo('1')
            ->andReverseTransformsTo('1', '1');

        FieldTransformationAssertion::assertThat($field)
            ->withInput('-1')
            ->successfullyTransformsTo('-1')
            ->andReverseTransformsTo('-1');
    }

    public function testWrongInputFails()
    {
        $field = $this->getFactory()->createField('integer', NumberType::class);

        FieldTransformationAssertion::assertThat($field)
            ->withInput('foo')
            ->failsToTransforms();
    }

    public function testDefaultFormatting()
    {
        $field = $this->getFactory()->createField('number', NumberType::class);

        FieldTransformationAssertion::assertThat($field)
            ->withInput('12345,67890', '12345.67890')
            ->successfullyTransformsTo('12345.67890')
            ->andReverseTransformsTo('12345,679', '12345.67890');

        FieldTransformationAssertion::assertThat($field)
            ->withInput('12345,679', '12345.679')
            ->successfullyTransformsTo('12345.679')
            ->andReverseTransformsTo('12345,679', '12345.679');
    }

    public function testNonWesternFormatting()
    {
        \Locale::setDefault('ar');

        $field = $this->getFactory()->createField('number', NumberType::class);

        FieldTransformationAssertion::assertThat($field)
            ->withInput('١٢٣٤٥٫٦٧٨٩٠', '12345.67890')
            ->successfullyTransformsTo('12345.6789')
            ->andReverseTransformsTo('١٢٣٤٥٫٦٧٩', '12345.6789');

        FieldTransformationAssertion::assertThat($field)
            ->withInput('١٢٣٤٥٫٦٧٩', '12345.679')
            ->successfullyTransformsTo('12345.679')
            ->andReverseTransformsTo('١٢٣٤٥٫٦٧٩', '12345.679');
    }

    public function testDefaultFormattingWithGrouping()
    {
        $field = $this->getFactory()->createField('number', NumberType::class, ['grouping' => true]);

        FieldTransformationAssertion::assertThat($field)
            ->withInput('12.345,679', '12345.679')
            ->successfullyTransformsTo('12345.679')
            ->andReverseTransformsTo('12.345,679', '12345.679');

        FieldTransformationAssertion::assertThat($field)
            ->withInput('12345,679', '12345.679')
            ->successfullyTransformsTo('12345.679')
            ->andReverseTransformsTo('12.345,679', '12345.679');
    }

    public function testDefaultFormattingWithPrecision()
    {
        $field = $this->getFactory()->createField('number', NumberType::class, ['precision' => 2]);

        FieldTransformationAssertion::assertThat($field)
            ->withInput('12345,67890', '12345.67890')
            ->successfullyTransformsTo('12345.68')
            ->andReverseTransformsTo('12345,68', '12345.68');

        FieldTransformationAssertion::assertThat($field)
            ->withInput('12345,67', '12345.67')
            ->successfullyTransformsTo('12345.67')
            ->andReverseTransformsTo('12345,67', '12345.67');
    }

    public function testDefaultFormattingWithRounding()
    {
        $field = $this->getFactory()->createField('number', NumberType::class, [
            'precision' => 0, 'rounding_mode' => \NumberFormatter::ROUND_UP,
        ]);

        FieldTransformationAssertion::assertThat($field)
            ->withInput('12345,54321', '12345.54321')
            ->successfullyTransformsTo('12346')
            ->andReverseTransformsTo('12346');

        FieldTransformationAssertion::assertThat($field)
            ->withInput('12345')
            ->successfullyTransformsTo('12345')
            ->andReverseTransformsTo('12345');
    }

    public function testViewIsConfiguredProperly()
    {
        $field = $this->getFactory()->createField('number', NumberType::class, [
            'precision' => 0, 'grouping' => false,
        ]);

        $field->finalizeConfig();
        $fieldView = $field->createView(new FieldSetView());

        self::assertArrayHasKey('precision', $fieldView->vars);
        self::assertArrayHasKey('grouping', $fieldView->vars);

        self::assertEquals(0, $fieldView->vars['precision']);
        self::assertFalse($fieldView->vars['grouping']);
    }

    protected function setUp()
    {
        parent::setUp();

        // we test against "de_DE", so we need the full implementation
        IntlTestHelper::requireFullIntl($this, '58.1');

        \Locale::setDefault('de_DE');
    }
}
