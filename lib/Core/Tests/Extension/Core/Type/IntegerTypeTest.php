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

use Rollerworks\Component\Search\Extension\Core\Type\IntegerType;
use Rollerworks\Component\Search\FieldSetView;
use Rollerworks\Component\Search\Test\FieldTransformationAssertion;
use Rollerworks\Component\Search\Test\SearchIntegrationTestCase;
use Symfony\Component\Intl\Util\IntlTestHelper;

/**
 * @internal
 */
final class IntegerTypeTest extends SearchIntegrationTestCase
{
    public function testCreate()
    {
        $field = $this->getFactory()->createField('integer', IntegerType::class);

        self::assertFalse($field->getOption('grouping'));
    }

    public function testCastsToInteger()
    {
        $field = $this->getFactory()->createField('integer', IntegerType::class);

        FieldTransformationAssertion::assertThat($field)
            ->withInput('1.678')
            ->successfullyTransformsTo(1)
            ->andReverseTransformsTo('1');

        FieldTransformationAssertion::assertThat($field)
            ->withInput('1')
            ->successfullyTransformsTo(1)
            ->andReverseTransformsTo('1');

        FieldTransformationAssertion::assertThat($field)
            ->withInput('-1')
            ->successfullyTransformsTo(-1)
            ->andReverseTransformsTo('-1');
    }

    public function testNonWesternFormatting()
    {
        \Locale::setDefault('ar');

        $field = $this->getFactory()->createField('number', IntegerType::class);

        FieldTransformationAssertion::assertThat($field)
            ->withInput('١٢٣٤٥٫٦٧٨٩٠', '12345')
            ->successfullyTransformsTo(12345)
            ->andReverseTransformsTo('١٢٣٤٥', '12345');

        FieldTransformationAssertion::assertThat($field)
            ->withInput('١٢٣٤٥', '12345.679')
            ->successfullyTransformsTo(12345)
            ->andReverseTransformsTo('١٢٣٤٥', '12345');
    }

    public function testWrongInputFails()
    {
        $field = $this->getFactory()->createField('integer', IntegerType::class);

        FieldTransformationAssertion::assertThat($field)->withInput('foo')->failsToTransforms();
    }

    public function testViewIsConfiguredProperly()
    {
        $field = $this->getFactory()->createField(
            'integer',
            IntegerType::class,
            [
                'grouping' => false,
            ]
        );

        $field->finalizeConfig();
        $fieldView = $field->createView(new FieldSetView());

        self::assertArrayHasKey('grouping', $fieldView->vars);
        self::assertArrayNotHasKey('precision', $fieldView->vars);
        self::assertFalse($fieldView->vars['grouping']);
    }

    protected function setUp()
    {
        parent::setUp();

        // we test against "ar", so we need the full implementation
        IntlTestHelper::requireFullIntl($this, '58.1');

        \Locale::setDefault('en_us');
    }
}
