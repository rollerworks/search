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
use Rollerworks\Component\Search\Exception\TransformationFailedException;
use Rollerworks\Component\Search\Extension\Core\DataTransformer\BooleanToLocalizedValueTransformer;

/**
 * @internal
 */
final class BooleanToLocalizedValueTransformerTest extends TestCase
{
    /** @test */
    public function it_transforms_from_string(): void
    {
        $transformer = new BooleanToLocalizedValueTransformer();

        self::assertNull($transformer->transform(null));
        self::assertNull($transformer->reverseTransform(null));
        self::assertNull($transformer->reverseTransform(''));

        self::assertEquals('yes', $transformer->transform(true));
        self::assertEquals('no', $transformer->transform(false));

        self::assertTrue($transformer->reverseTransform('true'));
        self::assertFalse($transformer->reverseTransform('false'));

        // Label Aliases
        self::assertTrue($transformer->reverseTransform('yes'));
        self::assertFalse($transformer->reverseTransform('no'));

        // Case insensitive
        self::assertTrue($transformer->reverseTransform('YES'));
        self::assertFalse($transformer->reverseTransform('NO'));

        // Numeric values
        self::assertTrue($transformer->reverseTransform('1'));
        self::assertFalse($transformer->reverseTransform('0'));
        self::assertTrue($transformer->reverseTransform(1));
        self::assertFalse($transformer->reverseTransform(0));
    }

    /** @test */
    public function it_fails_with_none_scalar(): void
    {
        $transformer = new BooleanToLocalizedValueTransformer();

        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Expected a boolean value, got "array".');

        $transformer->transform([]);
    }

    /** @test */
    public function it_fails_with_none_scalar_reverse(): void
    {
        $transformer = new BooleanToLocalizedValueTransformer();

        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Expected a scalar value, got "array".');

        $transformer->reverseTransform([]);
    }

    /** @test */
    public function it_when_reverse_value_is_not_supported(): void
    {
        $transformer = new BooleanToLocalizedValueTransformer();

        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Expected one of ("true", "1", 1, "on", "yes") or ("false", "0", 0, "off", "no"), got "foo".');

        $transformer->reverseTransform('foo');
    }

    /** @test */
    public function it_when_numeric_reverse_value_is_not_supported(): void
    {
        $transformer = new BooleanToLocalizedValueTransformer();

        $this->expectException(TransformationFailedException::class);
        $this->expectExceptionMessage('Expected one of ("true", "1", 1, "on", "yes") or ("false", "0", 0, "off", "no"), got 4.');

        $transformer->reverseTransform(4);
    }
}
