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

namespace Rollerworks\Component\Search\Tests;

use PHPUnit\Framework\TestCase;
use Rollerworks\Component\Search\Exception\InvalidArgumentException;
use Rollerworks\Component\Search\FieldSetConfigurator;
use Rollerworks\Component\Search\LazyFieldSetRegistry;

/**
 * @internal
 */
final class LazyFieldSetRegistryTest extends TestCase
{
    /** @test */
    public function it_loads_configurator_lazily(): void
    {
        $configurator = $this->createMock(FieldSetConfigurator::class);
        $configurator2 = $this->createMock(FieldSetConfigurator::class);

        $registry = LazyFieldSetRegistry::create(
            [
                'set' => static fn () => $configurator,
                'set2' => static fn () => $configurator2,
            ]
        );

        self::assertTrue($registry->hasConfigurator('set'));
        self::assertTrue($registry->hasConfigurator('set2'));

        self::assertSame($configurator, $registry->getConfigurator('set'));
        self::assertSame($configurator2, $registry->getConfigurator('set2'));

        // Ensure they still work, after initializing.
        self::assertFalse($registry->hasConfigurator('set3'));
        self::assertTrue($registry->hasConfigurator('set'));

        self::assertSame($configurator, $registry->getConfigurator('set'));
        self::assertSame($configurator2, $registry->getConfigurator('set2'));
    }

    /** @test */
    public function it_loads_configurator_by_fqcn(): void
    {
        $configurator = $this->createMock(FieldSetConfigurator::class);
        $configurator2 = $this->createMock(FieldSetConfigurator::class);

        $registry = LazyFieldSetRegistry::create(
            [
                'set' => static fn () => $configurator,
            ]
        );

        $name = $configurator2::class;

        self::assertTrue($registry->hasConfigurator('set'));
        self::assertTrue($registry->hasConfigurator($name));
        self::assertFalse($registry->hasConfigurator('set2'));

        self::assertSame($configurator, $registry->getConfigurator('set'));
        self::assertSame($name, \get_class($registry->getConfigurator($name)));
    }

    /** @test */
    public function it_checks_registered_before_class_name(): void
    {
        $configurator = $this->createMock(FieldSetConfigurator::class);
        $configurator2 = $this->createMock(FieldSetConfigurator::class);
        $name = $configurator2::class;

        $registry = LazyFieldSetRegistry::create(
            [
                'set' => static fn () => $configurator,
                $name => static fn () => $configurator2,
            ]
        );

        $name = $configurator2::class;

        self::assertTrue($registry->hasConfigurator('set'));
        self::assertTrue($registry->hasConfigurator($name));
        self::assertFalse($registry->hasConfigurator('set2'));

        self::assertSame($configurator, $registry->getConfigurator('set'));
        self::assertSame($configurator2, $registry->getConfigurator($name));
    }

    /** @test */
    public function it_errors_when_configurator_is_not_registered_and_class_is_a_configurator(): void
    {
        $configurator = $this->createMock(FieldSetConfigurator::class);
        $configurator2 = \stdClass::class;

        $registry = LazyFieldSetRegistry::create(
            [
                'set' => static fn () => $configurator,
            ]
        );

        self::assertTrue($registry->hasConfigurator('set'));
        self::assertFalse($registry->hasConfigurator('set2'));
        self::assertFalse($registry->hasConfigurator($configurator2));

        self::assertSame($configurator, $registry->getConfigurator('set'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Configurator class "stdClass" is expected to be an instance of ');

        $registry->getConfigurator($configurator2);
    }

    /** @test */
    public function it_errors_when_configurator_is_not_registered_class_does_not_exist(): void
    {
        $configurator = $this->createMock(FieldSetConfigurator::class);
        $configurator2 = 'f4394832948_foobar_cow';

        $registry = LazyFieldSetRegistry::create(
            [
                'set' => static fn () => $configurator,
            ]
        );

        self::assertTrue($registry->hasConfigurator('set'));
        self::assertFalse($registry->hasConfigurator('set2'));
        self::assertFalse($registry->hasConfigurator($configurator2));

        self::assertSame($configurator, $registry->getConfigurator('set'));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Could not load FieldSet configurator "f4394832948_foobar_cow"');

        $registry->getConfigurator($configurator2);
    }
}
