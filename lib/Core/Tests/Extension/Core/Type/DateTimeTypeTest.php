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

use Rollerworks\Component\Search\Extension\Core\Type\DateTimeType;
use Rollerworks\Component\Search\FieldSetView;
use Rollerworks\Component\Search\Test\FieldTransformationAssertion;
use Rollerworks\Component\Search\Test\SearchIntegrationTestCase;
use Symfony\Component\Intl\Util\IntlTestHelper;

/**
 * @internal
 */
final class DateTimeTypeTest extends SearchIntegrationTestCase
{
    public function testViewTimezoneCanBeTransformedToModelTimezone()
    {
        $field = $this->getFactory()->createField('datetime', DateTimeType::class, [
            'model_timezone' => 'America/New_York',
            'view_timezone' => 'Pacific/Tahiti',
            'pattern' => 'yyyy-MM-dd\'T\'HH:mm:ssZZZZZ',
            'html5' => true,
        ]);

        $outputTime = new \DateTime('2010-06-02 03:04:00 Pacific/Tahiti');
        $outputTime->setTimezone(new \DateTimeZone('America/New_York'));

        FieldTransformationAssertion::assertThat($field)
            ->withInput('2010-06-02T03:04:00-10:00', '2010-06-02T03:04:00-10:00')
            ->successfullyTransformsTo($outputTime)
            ->andReverseTransformsTo('2010-06-02T03:04:00-10:00', '2010-06-02T03:04:00');
    }

    public function testPatternCanBeConfigured()
    {
        $field = $this->getFactory()->createField('datetime', DateTimeType::class, [
            'pattern' => 'MM*yyyy*dd HH:mm',
        ]);

        $outputTime = new \DateTime('2010-06-02T13:12:00.000000+0000');

        FieldTransformationAssertion::assertThat($field)
            ->withInput($outputTime->format('m*Y*d H:i'), $outputTime->format('c'))
            ->successfullyTransformsTo($outputTime)
            ->andReverseTransformsTo('06*2010*02 13:12', '2010-06-02T13:12:00Z');
    }

    public function testTimeFormatCanBeConfigurable()
    {
        $field = $this->getFactory()->createField('datetime', DateTimeType::class, [
            'model_timezone' => 'UTC',
            'view_timezone' => 'UTC',
            'time_format' => \IntlDateFormatter::SHORT,
        ]);

        $outputTime = new \DateTime('2010-06-02 03:04:00 UTC');

        FieldTransformationAssertion::assertThat($field)
            ->withInput('Jun 2, 2010, 3:04 AM', '2010-06-02T03:04:00Z')
            ->successfullyTransformsTo($outputTime)
            ->andReverseTransformsTo('Jun 2, 2010, 3:04 AM', '2010-06-02T03:04:00Z');
    }

    public function testInvalidInputShouldFailTransformation()
    {
        $field = $this->getFactory()->createField('datetime', DateTimeType::class, [
            'pattern' => 'MM-yyyy-dd',
        ]);

        FieldTransformationAssertion::assertThat($field)
            ->withInput('06*2010*02', '2010-06-02T13:12:00Z')
            ->failsToTransforms();

        FieldTransformationAssertion::assertThat($field)
            ->withInput('06-2010-02', '2010-06*02T13:12:00Z')
            ->failsToTransforms();
    }

    public function testViewIsConfiguredProperly()
    {
        $field = $this->getFactory()->createField('datetime', DateTimeType::class, [
            'date_format' => \IntlDateFormatter::SHORT,
            'time_format' => \IntlDateFormatter::SHORT,
        ]);

        $field->finalizeConfig();
        $fieldView = $field->createView(new FieldSetView());

        self::assertArrayHasKey('timezone', $fieldView->vars);
        self::assertArrayHasKey('pattern', $fieldView->vars);

        self::assertEquals(date_default_timezone_get(), $fieldView->vars['timezone']);
        self::assertEquals('M/d/yy, h:mm a', $fieldView->vars['pattern']);
    }

    protected function setUp(): void
    {
        IntlTestHelper::requireIntl($this, '58.1');

        parent::setUp();
    }
}
