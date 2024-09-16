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

namespace Rollerworks\Component\Search\Tests\Extension;

use PHPUnit\Framework\TestCase;
use Rollerworks\Component\Search\Exception\InvalidArgumentException;
use Rollerworks\Component\Search\Extension\Core\Type\IntegerType;
use Rollerworks\Component\Search\Extension\Core\Type\NumberType;
use Rollerworks\Component\Search\Extension\Core\Type\TextType;
use Rollerworks\Component\Search\Extension\LazyExtension;
use Rollerworks\Component\Search\Field\FieldTypeExtension;

/**
 * @internal
 */
final class LazyExtensionTest extends TestCase
{
    /** @test */
    public function it_allows_creating_without_types(): void
    {
        $extension = LazyExtension::create([]);

        self::assertFalse($extension->hasType('something'));
        self::assertFalse($extension->hasType(TextType::class));
        self::assertEmpty($extension->getTypeExtensions(TextType::class));
    }

    /** @test */
    public function it_loads_registered_type(): void
    {
        $extension = LazyExtension::create(
            [
                TextType::class => static fn () => new TextType(),
            ]
        );

        self::assertTrue($extension->hasType(TextType::class));
        self::assertInstanceOf(TextType::class, $extension->getType(TextType::class));
        self::assertInstanceOf(TextType::class, $extension->getType(TextType::class));
    }

    /** @test */
    public function it_fails_when_requested_type_is_not_registered(): void
    {
        $extension = LazyExtension::create([]);

        self::assertFalse($extension->hasType(TextType::class));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The field type "' . TextType::class . '" is not registered with the service container.');

        $extension->getType(TextType::class);
    }

    /** @test */
    public function it_loads_type_extensions(): void
    {
        $typeExtension1 = $this->createTypeExtension(TextType::class);
        $typeExtension2 = $this->createTypeExtension(TextType::class);
        $typeExtension3 = $this->createTypeExtension(IntegerType::class);

        $extension = LazyExtension::create(
            [
                TextType::class => static fn () => new TextType(),
            ],
            [
                TextType::class => [$typeExtension1, $typeExtension2],
                IntegerType::class => [$typeExtension3],
            ]
        );

        self::assertTrue($extension->hasType(TextType::class));
        self::assertInstanceOf(TextType::class, $extension->getType(TextType::class));
        self::assertInstanceOf(TextType::class, $extension->getType(TextType::class));

        self::assertSame([$typeExtension1, $typeExtension2], $extension->getTypeExtensions(TextType::class));
        self::assertSame([$typeExtension3], $extension->getTypeExtensions(IntegerType::class));
        self::assertEmpty($extension->getTypeExtensions(NumberType::class));
    }

    /** @test */
    public function it_checks_type_extension_parent_equality(): void
    {
        $typeExtension1 = $this->createTypeExtension(TextType::class);

        $extension = LazyExtension::create(
            [
                TextType::class => static fn () => new TextType(),
            ],
            [
                IntegerType::class => ['extension_1' => $typeExtension1],
            ]
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            \sprintf(
                'The extended type specified for the service "%s" does not match the actual extended type. Expected "%s", given "%s".',
                'extension_1',
                IntegerType::class,
                TextType::class
            )
        );

        $extension->getTypeExtensions(IntegerType::class);
    }

    public function createTypeExtension(string $type)
    {
        $typeExtension = $this->createMock(FieldTypeExtension::class);
        $typeExtension
            ->expects(self::any())
            ->method('getExtendedType')
            ->willReturn($type)
        ;

        return $typeExtension;
    }
}
