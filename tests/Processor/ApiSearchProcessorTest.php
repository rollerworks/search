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

namespace Rollerworks\Component\Search\ApiPlatform\Tests\Processor;

use Psr\SimpleCache\CacheInterface;
use Rollerworks\Component\Search\ApiPlatform\Processor\ApiSearchProcessor;
use Rollerworks\Component\Search\ApiPlatform\Tests\Fixtures\BookFieldSet;
use Rollerworks\Component\Search\Exception\InvalidSearchConditionException;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\Loader\ConditionExporterLoader;
use Rollerworks\Component\Search\Loader\InputProcessorLoader;
use Rollerworks\Component\Search\Processor\ProcessorConfig;
use Rollerworks\Component\Search\Processor\SearchPayload;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\Test\SearchIntegrationTestCase;
use Rollerworks\Component\Search\Value\ValuesBag;
use Rollerworks\Component\Search\Value\ValuesGroup;
use Symfony\Component\HttpFoundation\Request;

final class ApiSearchProcessorTest extends SearchIntegrationTestCase
{
    /**
     * @var FieldSet
     */
    private $fieldSet;

    /**
     * @var SearchCondition
     */
    private $condition;

    /**
     * @var SearchCondition
     */
    private $conditionOptimized;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|CacheInterface
     */
    private $cache;

    protected function setUp()
    {
        parent::setUp();

        $this->cache = $this->createMock(CacheInterface::class);
        $this->fieldSet = $this->getFactory()->createFieldSet(BookFieldSet::class);
        $this->condition = new SearchCondition(
            $this->fieldSet,
            (new ValuesGroup())->addField('title', (new ValuesBag())->addSimpleValue('Symfony'))
        );

        $this->conditionOptimized = new SearchCondition(
            $this->fieldSet,
            (new ValuesGroup())->addField(
                'title',
                (new ValuesBag())->addSimpleValue('Symfony')->addSimpleValue('Symfony')->removeSimpleValue(0)
            )
        );
    }

    private function createProcessor()
    {
        $processor = new ApiSearchProcessor(
            $this->getFactory(),
            InputProcessorLoader::create(),
            ConditionExporterLoader::create(),
            $this->cache
        );

        return $processor;
    }

    public function testProcessingEmptyRequestIsValid()
    {
        $config = new ProcessorConfig($this->fieldSet);
        $request = Request::create('/books', 'GET');

        $payload = $this->createProcessor()->processRequest($request, $config);

        self::assertFalse($payload->isChanged());
        self::assertTrue($payload->isValid());
        self::assertEmpty($payload->messages);
        self::assertNull($payload->searchCondition);
        self::assertNull($payload->exportedFormat);
        self::assertEmpty($payload->searchCode);
    }

    public function testProcessSearchCodeFromQuery()
    {
        $config = new ProcessorConfig($this->fieldSet);
        $request = Request::create('/books', 'GET', ['search' => ['fields' => ['title' => ['simple-values' => ['Symfony']]]]]);

        $payload = $this->createProcessor()->processRequest($request, $config);

        self::assertFalse($payload->isChanged());
        self::assertTrue($payload->isValid());
        self::assertEmpty($payload->messages);
        self::assertEquals($this->condition, $payload->searchCondition);
        self::assertEquals('array', $payload->exportedFormat);
        self::assertEquals(['fields' => ['title' => ['simple-values' => ['Symfony']]]], $payload->exportedCondition);
        self::assertEquals('fields%5Btitle%5D%5Bsimple-values%5D%5B0%5D=Symfony', $payload->searchCode);
    }

    public function testProcessSearchCodeWithChanges()
    {
        $config = new ProcessorConfig($this->fieldSet);
        $request = Request::create('/books', 'GET', ['search' => ['fields' => ['title' => ['simple-values' => ['Symfony', 'Symfony']]]]]);

        $payload = $this->createProcessor()->processRequest($request, $config);

        self::assertTrue($payload->isChanged());
        self::assertTrue($payload->isValid());
        self::assertEmpty($payload->messages);
        self::assertEquals($this->conditionOptimized, $payload->searchCondition);
        self::assertEquals('array', $payload->exportedFormat);
        self::assertEquals(['fields' => ['title' => ['simple-values' => ['Symfony']]]], $payload->exportedCondition);
        self::assertEquals('fields%5Btitle%5D%5Bsimple-values%5D%5B0%5D=Symfony', $payload->searchCode);
    }

    public function testSearchCodeOnlyAcceptsArray()
    {
        $config = new ProcessorConfig($this->fieldSet);
        $request = Request::create('/books', 'GET', ['search' => '']);

        $payload = $this->createProcessor()->processRequest($request, $config);

        self::assertTrue($payload->isValid());
        self::assertFalse($payload->isChanged());
        self::assertNull($payload->searchCondition);
        self::assertEquals('', $payload->exportedCondition);
        self::assertEquals('', $payload->searchCode);
    }

    public function testSearchCodeWithSyntaxErrorIsInvalid()
    {
        $config = new ProcessorConfig($this->fieldSet);
        $request = Request::create('/books', 'GET', ['search' => ['fields' => ['id' => ['simple-values' => ['He']]]]]);

        // Unlike other processors this will throw the exception globally so the ExceptionListener can
        // catch it. And no rethrow is required.
        $this->expectException(InvalidSearchConditionException::class);

        $this->createProcessor()->processRequest($request, $config);
    }

    public function testProcessSearchCodeFromQueryWithNoCache()
    {
        $config = new ProcessorConfig($this->fieldSet);
        $request = Request::create('/books', 'GET', ['search' => ['fields' => ['title' => ['simple-values' => ['Symfony']]]]]);

        $this->cache
            ->expects(self::once())
            ->method('get')
            ->with('8eb42c0970710702a40e010b470f6586b8301117231dd4fdd1a51576c293f89f')
            ->willReturn(null);

        $storedPayload = new SearchPayload(false);
        $storedPayload->searchCondition = $this->getFactory()->getSerializer()->serialize($this->condition);
        $storedPayload->searchCode = 'fields%5Btitle%5D%5Bsimple-values%5D%5B0%5D=Symfony';
        $storedPayload->exportedCondition = ['fields' => ['title' => ['simple-values' => ['Symfony']]]];
        $storedPayload->exportedFormat = 'array';
        $storedPayload->messages = [];

        $this->cache
            ->expects(self::once())
            ->method('set')
            ->with('8eb42c0970710702a40e010b470f6586b8301117231dd4fdd1a51576c293f89f', $storedPayload);

        $payload = $this->createProcessor()->processRequest($request, $config);

        self::assertFalse($payload->isChanged());
        self::assertTrue($payload->isValid());
        self::assertEmpty($payload->messages);
        self::assertEquals($this->condition, $payload->searchCondition);
        self::assertEquals('array', $payload->exportedFormat);
        self::assertEquals(['fields' => ['title' => ['simple-values' => ['Symfony']]]], $payload->exportedCondition);
        self::assertEquals('fields%5Btitle%5D%5Bsimple-values%5D%5B0%5D=Symfony', $payload->searchCode);
    }

    public function testProcessSearchCodeFromQueryStoresOptimized()
    {
        $config = new ProcessorConfig($this->fieldSet);
        $request = Request::create('/books', 'GET', ['search' => ['fields' => ['title' => ['simple-values' => ['Symfony', 'Symfony']]]]]);

        $this->cache
            ->expects(self::once())
            ->method('get')
            ->with('dfb6a7d17a8380b5aea06bb965d7de340cd3ad25df93d7469cf84cb7ed0fb37c')
            ->willReturn(null);

        $storedPayload = new SearchPayload(false);
        $storedPayload->searchCondition = $this->getFactory()->getSerializer()->serialize($this->conditionOptimized);
        $storedPayload->searchCode = 'fields%5Btitle%5D%5Bsimple-values%5D%5B0%5D=Symfony';
        $storedPayload->exportedCondition = ['fields' => ['title' => ['simple-values' => [0 => 'Symfony']]]];
        $storedPayload->exportedFormat = 'array';
        $storedPayload->messages = [];

        $this->cache
            ->expects(self::once())
            ->method('set')
            ->with('8eb42c0970710702a40e010b470f6586b8301117231dd4fdd1a51576c293f89f', $storedPayload);

        $payload = $this->createProcessor()->processRequest($request, $config);

        self::assertTrue($payload->isChanged());
        self::assertTrue($payload->isValid());
        self::assertEmpty($payload->messages);
        self::assertEquals($this->conditionOptimized, $payload->searchCondition);
        self::assertEquals('array', $payload->exportedFormat);
        self::assertEquals(['fields' => ['title' => ['simple-values' => [0 => 'Symfony']]]], $payload->exportedCondition);
        self::assertEquals('fields%5Btitle%5D%5Bsimple-values%5D%5B0%5D=Symfony', $payload->searchCode);
    }

    public function testProcessSearchCodeFromQueryWithExistingCache()
    {
        // This condition would have given an transformer error,
        // so getting one means the transformer was never executed.
        $config = new ProcessorConfig($this->fieldSet);
        $request = Request::create('/books', 'GET', ['search' => ['fields' => ['id' => ['simple-values' => ['He']]]]]);

        $storedPayload = new SearchPayload(false);
        $storedPayload->searchCondition = $this->getFactory()->getSerializer()->serialize($this->condition);
        $storedPayload->searchCode = 'fields[id][simple-values][0]=He';
        $storedPayload->exportedCondition = ['fields' => ['id' => ['simple-values' => ['He']]]];
        $storedPayload->exportedFormat = 'array';
        $storedPayload->messages = [];

        $this->cache
            ->expects(self::once())
            ->method('get')
            ->with('46c9f402cb1b5d2208ed62e0a9242cd2f43891a48949eb4c477ad2a053b81355')
            ->willReturn($storedPayload);

        $payload = $this->createProcessor()->processRequest($request, $config);

        self::assertFalse($payload->isChanged());
        self::assertTrue($payload->isValid());
        self::assertEmpty($payload->messages);
        self::assertEquals($this->condition, $payload->searchCondition);
        self::assertEquals('array', $payload->exportedFormat);
        self::assertEquals(['fields' => ['id' => ['simple-values' => ['He']]]], $payload->exportedCondition);
        self::assertEquals('fields[id][simple-values][0]=He', $payload->searchCode);

        $this->cache
            ->expects(self::never())
            ->method('set');
    }

    public function testProcessSearchCodeWithInvalidCacheIsDeletedAndReProcessed()
    {
        $config = new ProcessorConfig($this->fieldSet);
        $request = Request::create('/books', 'GET', ['search' => ['fields' => ['title' => ['simple-values' => ['Symfony']]]]]);

        $storedPayload = new SearchPayload(false);
        $storedPayload->searchCondition = [$this->condition->getFieldSet()->getSetName(), 'invalid-I-am-I-am'];
        $storedPayload->searchCode = 'fields[id][simple-values][0]=He';
        $storedPayload->exportedCondition = ['fields' => ['id' => ['simple-values' => ['He']]]];
        $storedPayload->exportedFormat = 'array';
        $storedPayload->messages = [];

        $this->cache
            ->expects(self::once())
            ->method('get')
            ->with('8eb42c0970710702a40e010b470f6586b8301117231dd4fdd1a51576c293f89f')
            ->willReturn($storedPayload);

        $this->cache
            ->expects(self::once())
            ->method('delete')
            ->with('8eb42c0970710702a40e010b470f6586b8301117231dd4fdd1a51576c293f89f');

        $payload = new SearchPayload();
        $payload->searchCondition = $this->condition;
        $payload->searchCode = 'fields%5Btitle%5D%5Bsimple-values%5D%5B0%5D=Symfony';
        $payload->exportedCondition = ['fields' => ['title' => ['simple-values' => ['Symfony']]]];
        $payload->exportedFormat = 'array';
        $payload->messages = [];

        $newPayload = clone $payload;
        $newPayload->searchCondition = $this->getFactory()->getSerializer()->serialize($this->condition);

        $this->cache
            ->expects(self::once())
            ->method('set')
            ->with('8eb42c0970710702a40e010b470f6586b8301117231dd4fdd1a51576c293f89f', $newPayload);

        $payload = $this->createProcessor()->processRequest($request, $config);

        self::assertFalse($payload->isChanged());
        self::assertTrue($payload->isValid());
        self::assertEmpty($payload->messages);
        self::assertEquals($this->condition, $payload->searchCondition);
        self::assertEquals('array', $payload->exportedFormat);
        self::assertEquals(['fields' => ['title' => ['simple-values' => ['Symfony']]]], $payload->exportedCondition);
        self::assertEquals('fields%5Btitle%5D%5Bsimple-values%5D%5B0%5D=Symfony', $payload->searchCode);
    }
}
