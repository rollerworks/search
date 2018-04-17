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
use Rollerworks\Component\Search\Exporter;
use Rollerworks\Component\Search\Loader\ConditionExporterLoader;

/**
 * @internal
 */
final class ConditionExporterLoaderTest extends TestCase
{
    /** @test */
    public function it_lazily_loads_a_condition_exporter()
    {
        $loader = ConditionExporterLoader::create();
        $processor = $loader->get('json');

        self::assertSame($processor, $loader->get('json'));
    }

    /**
     * @dataProvider provideProcessors
     * @test
     */
    public function it_can_load_bundled_processor(string $alias, string $className)
    {
        $loader = ConditionExporterLoader::create();

        self::assertInstanceOf($className, $loader->get($alias));
    }

    public function provideProcessors(): array
    {
        return [
            ['json', Exporter\JsonExporter::class],
            ['array', Exporter\ArrayExporter::class],
            ['string_query', Exporter\StringQueryExporter::class],
            ['norm_string_query', Exporter\NormStringQueryExporter::class],
        ];
    }

    /** @test */
    public function it_fails_for_unsupported_processor()
    {
        $loader = ConditionExporterLoader::create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Enable to load exporter, format "form" has no registered exporter.');

        $loader->get('form');
    }
}
