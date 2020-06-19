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
use Prophecy\Argument;
use Rollerworks\Component\Search\DataTransformer;
use Rollerworks\Component\Search\Exception\TransformationFailedException;
use Rollerworks\Component\Search\Extension\Core\DataTransformer\LocalizedBirthdayTransformer;
use Symfony\Component\Intl\Util\IntlTestHelper;

/**
 * @internal
 */
final class LocalizedBirthdayTransformerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        \Locale::setDefault('en');
    }

    /** @test */
    public function it_transforms_age_to_integer()
    {
        $dateTransformer = $this->prophesize(DataTransformer::class);
        $dateTransformer->reverseTransform(Argument::any())->shouldNotBeCalled();
        $dateTransformer->transform(Argument::any())->shouldNotBeCalled();

        $transformer = new LocalizedBirthdayTransformer($dateTransformer->reveal());

        self::assertEquals(18, $transformer->reverseTransform('18'));
        self::assertEquals('18', $transformer->transform(18));

        self::assertEquals(18000, $transformer->reverseTransform('18000'));
        self::assertEquals('18000', $transformer->transform(18000));
    }

    /** @test */
    public function it_transforms_ar_localized_age_to_integer()
    {
        // Since we test against other locales, we need the full implementation
        IntlTestHelper::requireFullIntl($this, false);

        \Locale::setDefault('ar');

        $dateTransformer = $this->prophesize(DataTransformer::class);
        $dateTransformer->reverseTransform(Argument::any())->shouldNotBeCalled();
        $dateTransformer->transform(Argument::any())->shouldNotBeCalled();

        $transformer = new LocalizedBirthdayTransformer($dateTransformer->reveal());

        self::assertEquals(79, $transformer->reverseTransform('٧٩'));
        self::assertEquals('٧٩', $transformer->transform(79));
    }

    /** @test */
    public function it_transforms_with_date()
    {
        $date = new \DateTime('2010-03-05 00:00:00 UTC');

        $dateTransformer = $this->prophesize(DataTransformer::class);
        $dateTransformer->reverseTransform('2010-03-05')->willReturn($date);
        $dateTransformer->transform($date)->willReturn('2010-03-05');

        $transformer = new LocalizedBirthdayTransformer($dateTransformer->reveal());

        self::assertEquals($date, $transformer->reverseTransform('2010-03-05'));
        self::assertEquals('2010-03-05', $transformer->transform($date));

        self::assertEquals(18, $transformer->reverseTransform('18'));
        self::assertEquals('18', $transformer->transform(18));
    }

    /** @test */
    public function it_allows_disabled_age()
    {
        $date = new \DateTime('2010-03-05 00:00:00 UTC');

        $dateTransformer = $this->prophesize(DataTransformer::class);
        $dateTransformer->reverseTransform('2010-03-05')->willReturn($date);
        $dateTransformer->transform($date)->willReturn('2010-03-05');

        $transformer = new LocalizedBirthdayTransformer($dateTransformer->reveal(), false);

        self::assertEquals($date, $transformer->reverseTransform('2010-03-05'));
        self::assertEquals('2010-03-05', $transformer->transform($date));

        try {
            $transformer->reverseTransform('18');

            $this->fail('Age should not be reverseTransformed.');
        } catch (TransformationFailedException $e) {
            self::assertEquals('Age support is not enabled.', $e->getMessage());
        }

        try {
            $transformer->transform(18);

            $this->fail('Age should not be transformed.');
        } catch (TransformationFailedException $e) {
            self::assertEquals('Age support is not enabled.', $e->getMessage());
        }
    }

    /** @test */
    public function it_disallows_date_in_the_future_by_default()
    {
        $dateObj = new \DateTime('tomorrow');

        $dateTransformer = $this->prophesize(DataTransformer::class);
        $dateTransformer->reverseTransform($dateObj->format('Y-m-d'))->willReturn($dateObj);

        $transformer = new LocalizedBirthdayTransformer($dateTransformer->reveal());

        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage(sprintf('Date "%s" is higher then current date ', $dateObj->format('Y-m-d')));

        $transformer->reverseTransform($dateObj->format('Y-m-d'));
    }

    /** @test */
    public function it_allows_date_in_the_future_when_enabled()
    {
        $dateObj = new \DateTime('tomorrow');

        $dateTransformer = $this->prophesize(DataTransformer::class);
        $dateTransformer->reverseTransform($dateObj->format('Y-m-d'))->willReturn($dateObj);
        $dateTransformer->transform($dateObj)->willReturn($dateObj->format('Y-m-d'));

        $transformer = new LocalizedBirthdayTransformer($dateTransformer->reveal(), false, true);

        self::assertEquals($dateObj, $transformer->reverseTransform($dateObj->format('Y-m-d')));
        self::assertEquals($dateObj->format('Y-m-d'), $transformer->transform($dateObj));
    }
}
