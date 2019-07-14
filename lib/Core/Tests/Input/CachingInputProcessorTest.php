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

namespace Rollerworks\Component\Search\Tests\Input;

use PHPUnit\Framework\TestCase;
use Psr\SimpleCache\CacheInterface;
use Rollerworks\Component\Search\Exception\InvalidArgumentException;
use Rollerworks\Component\Search\Input\CachingInputProcessor;
use Rollerworks\Component\Search\Input\ProcessorConfig;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\SearchConditionSerializer;
use Rollerworks\Component\Search\Tests\Mock\EmptyInputProcessorStub;
use Rollerworks\Component\Search\Tests\Mock\FieldSetStub;
use Rollerworks\Component\Search\Tests\Mock\SpyingInputProcessor;
use Rollerworks\Component\Search\Tests\Mock\StubInputProcessor;
use Rollerworks\Component\Search\Value\ValuesGroup;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Psr16Cache;

class CachingInputProcessorTest extends TestCase
{
    /** @test */
    public function it_ignores_caching_for_non_string_input()
    {
        $serializer = $this->createMock(SearchConditionSerializer::class);
        $inputProcessor = new SpyingInputProcessor();
        $cache = new Psr16Cache($arrayCache = new ArrayAdapter());
        $processor = new CachingInputProcessor($cache, $serializer, $inputProcessor);

        $processor->process($config = new ProcessorConfig(new FieldSetStub()), $input = ['Hello']);

        self::assertEmpty($arrayCache->getValues());
        self::assertEquals($config, $inputProcessor->getConfig());
        self::assertEquals($input, $inputProcessor->getInput());
    }

    /** @test */
    public function it_processes_with_no_existing_cache()
    {
        $serializer = $this->createMock(SearchConditionSerializer::class);
        $serializer
            ->expects(self::once())
            ->method('unserialize')
            ->willThrowException(new InvalidArgumentException());

        $inputProcessor = new SpyingInputProcessor();
        $cache = new Psr16Cache($arrayCache = new ArrayAdapter());
        $processor = new CachingInputProcessor($cache, $serializer, $inputProcessor);

        $processor->process($config = new ProcessorConfig(new FieldSetStub()), $input = 'Hello');

        self::assertCount(1, $arrayCache->getValues());
        self::assertEquals($config, $inputProcessor->getConfig());
        self::assertEquals($input, $inputProcessor->getInput());
    }

    /** @test */
    public function it_uses_cached_version_when_cache_is_valid()
    {
        $serializer = $this->createMock(SearchConditionSerializer::class);
        $serializer
            ->expects(self::once())
            ->method('unserialize')
            ->with(['cachedResult'])
            ->willReturn($condition = new SearchCondition(new FieldSetStub(), new ValuesGroup()));

        $inputProcessor = new SpyingInputProcessor();

        $cache = $this->createMock(CacheInterface::class);
        $cache
            ->expects(self::once())
            ->method('get')
            ->willReturn(['cachedResult']);

        $processor = new CachingInputProcessor($cache, $serializer, $inputProcessor);

        self::assertSame($condition, $processor->process(new ProcessorConfig(new FieldSetStub()), 'Hello'));
        self::assertNull($inputProcessor->getConfig());
        self::assertNull($inputProcessor->getInput());
    }

    /** @test */
    public function it_stores_processed_result_in_cache()
    {
        $serializer = $this->createMock(SearchConditionSerializer::class);
        $serializer
            ->expects(self::once())
            ->method('unserialize')
            ->willThrowException(new InvalidArgumentException());

        $serializer
            ->expects(self::once())
            ->method('serialize')
            ->with(SpyingInputProcessor::getCondition())
            ->willReturn(['noop', 'serializedResult']);

        $cache = $this->createMock(CacheInterface::class);
        $cache
            ->expects(self::once())
            ->method('get')
            ->willReturn([]);

        $cache
            ->expects(self::once())
            ->method('set')
            ->with('57844014a80a2251e25a05e3c94ffdc2f47cb6ff06b3e2dcc27c5d5124dff22a', ['noop', 'serializedResult']);

        $inputProcessor = new SpyingInputProcessor();
        $processor = new CachingInputProcessor($cache, $serializer, $inputProcessor);

        $processor->process($config = new ProcessorConfig(new FieldSetStub()), $input = 'Hello');

        self::assertEquals($config, $inputProcessor->getConfig());
        self::assertEquals($input, $inputProcessor->getInput());
    }

    /** @test */
    public function it_does_not_store_empty_condition()
    {
        $serializer = $this->createMock(SearchConditionSerializer::class);
        $serializer
            ->expects(self::once())
            ->method('unserialize')
            ->willThrowException(new InvalidArgumentException());

        $serializer
            ->expects(self::never())
            ->method('serialize');

        $cache = $this->createMock(CacheInterface::class);
        $cache
            ->expects(self::once())
            ->method('get')
            ->willReturn([]);

        $cache
            ->expects(self::never())
            ->method('set');

        $inputProcessor = new EmptyInputProcessorStub();
        $processor = new CachingInputProcessor($cache, $serializer, $inputProcessor);

        $processor->process(new ProcessorConfig(new FieldSetStub()), 'Hello');
    }

    /** @test */
    public function it_separates_caches_by_processor_class()
    {
        $serializer = $this->createMock(SearchConditionSerializer::class);
        $serializer
            ->expects(self::any())
            ->method('unserialize')
            ->willThrowException(new InvalidArgumentException());

        $cache = new Psr16Cache($arrayCache = new ArrayAdapter());

        $processor = new CachingInputProcessor($cache, $serializer, new SpyingInputProcessor());
        $processor->process($config = new ProcessorConfig(new FieldSetStub()), $input = 'Hello');

        $processor2 = new CachingInputProcessor($cache, $serializer, new StubInputProcessor());
        $processor2->process($config2 = new ProcessorConfig(new FieldSetStub()), $input2 = 'Hello');

        self::assertCount(2, $arrayCache->getValues());
    }
}
