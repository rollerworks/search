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

namespace Rollerworks\Component\Search\Tests\Loader;

use PHPUnit\Framework\TestCase;
use Rollerworks\Component\Search\Exception\InvalidArgumentException;
use Rollerworks\Component\Search\Input;
use Rollerworks\Component\Search\Loader\InputProcessorLoader;

/**
 * @internal
 */
final class InputProcessorLoaderTest extends TestCase
{
    /** @test */
    public function it_lazily_loads_an_input_processor()
    {
        $loader = InputProcessorLoader::create();
        $processor = $loader->get('json');

        self::assertSame($processor, $loader->get('json'));
    }

    /**
     * @dataProvider provideProcessors
     * @test
     */
    public function it_can_load_bundled_processor(string $alias, string $className)
    {
        $loader = InputProcessorLoader::create();

        self::assertInstanceOf($className, $loader->get($alias));
    }

    public function provideProcessors(): array
    {
        return [
            ['json', Input\JsonInput::class],
            ['array', Input\ArrayInput::class],
            ['string_query', Input\StringQueryInput::class],
            ['norm_string_query', Input\NormStringQueryInput::class],
        ];
    }

    /** @test */
    public function it_fails_for_unsupported_processor()
    {
        $loader = InputProcessorLoader::create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Enable to load input-processor, "form" is not registered as processor.');

        $loader->get('form');
    }
}
