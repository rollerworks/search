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
use Rollerworks\Component\Search\Value\PatternMatch;
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
        self::assertEquals(new SearchCondition($config->getFieldSet(), new ValuesGroup()), $payload->searchCondition);
        self::assertNull($payload->exportedFormat);
        self::assertEmpty($payload->searchCode);
    }

    public function testProcessSearchCodeFromQueryAsString()
    {
        $config = new ProcessorConfig($this->fieldSet, 'norm_string_query');
        $request = Request::create('/books', 'GET', ['search' => 'title: Symfony;']);

        $payload = $this->createProcessor()->processRequest($request, $config);

        self::assertFalse($payload->isChanged());
        self::assertTrue($payload->isValid());
        self::assertEmpty($payload->messages);
        self::assertEquals($this->condition, $payload->searchCondition);
        self::assertEquals('norm_string_query', $payload->exportedFormat);
        self::assertEquals('title: Symfony;', $payload->exportedCondition);
        self::assertEquals('S:title%3A+Symfony%3B', $payload->searchCode);
    }

    public function testProcessSearchCodeFromQueryAsArray()
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
        self::assertEquals('A:{"fields":{"title":{"simple-values":["Symfony"]}}}', $payload->searchCode);
    }

    public function testProcessSearchCodeFromQueryWithBooleanConversion()
    {
        $config = new ProcessorConfig($this->fieldSet);
        $request = Request::create('/books', 'GET', [
            'search-format' => 'array',
            'search' => [
                'fields' => [
                    'title' => [
                        'pattern-matchers' => [
                            [
                                'type' => 'CONTAINS',
                                'value' => 'Symfony',
                                'case-insensitive' => '1',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $payload = $this->createProcessor()->processRequest($request, $config);

        self::assertFalse($payload->isChanged());
        self::assertTrue($payload->isValid());
        self::assertEmpty($payload->messages);
        self::assertEquals(
            new SearchCondition(
                $this->fieldSet,
                (new ValuesGroup())->addField('title', (new ValuesBag())->add(new PatternMatch('Symfony', PatternMatch::PATTERN_CONTAINS, true)))
            ),
            $payload->searchCondition
        );
        self::assertEquals('array', $payload->exportedFormat);
        self::assertSame(
            [
                'fields' => [
                    'title' => [
                        'pattern-matchers' => [
                            [
                                'type' => 'CONTAINS',
                                'value' => 'Symfony',
                                'case-insensitive' => '1',
                            ],
                        ],
                    ],
                ],
            ],
            $payload->exportedCondition
        );
        self::assertEquals(
            'A:{"fields":{"title":{"pattern-matchers":[{"type":"CONTAINS","value":"Symfony","case-insensitive":"1"}]}}}',
            $payload->searchCode
        );
    }

    public function testSearchCodeOnlyAcceptsArray()
    {
        $config = new ProcessorConfig($this->fieldSet);
        $request = Request::create('/books', 'GET', ['search' => '']);

        $payload = $this->createProcessor()->processRequest($request, $config);

        self::assertTrue($payload->isValid());
        self::assertFalse($payload->isChanged());
        self::assertEquals(new SearchCondition($config->getFieldSet(), new ValuesGroup()), $payload->searchCondition);
        self::assertEquals('', $payload->exportedCondition);
        self::assertEquals('', $payload->searchCode);
    }

    public function testSearchCodeWithSyntaxErrorIsInvalid()
    {
        $config = new ProcessorConfig($this->fieldSet);
        $request = Request::create('/books', 'GET', ['search' => 'id: he;']);

        // Unlike other processors this will throw the exception globally so the ExceptionListener can
        // catch it. And no rethrow is required.
        $this->expectException(InvalidSearchConditionException::class);

        $this->createProcessor()->processRequest($request, $config);
    }

    public function testProcessSearchCodeFromQueryWithNoCache()
    {
        $config = new ProcessorConfig($this->fieldSet, 'norm_string_query');
        $request = Request::create('/books', 'GET', ['search' => 'title: Symfony;']);

        $this->cache
            ->expects(self::once())
            ->method('get')
            ->with('f46ae6c70dbd72c18cf3d24623c30aedfc277831cc05f147d925ed26ebff54b1')
            ->willReturn(null);

        $storedPayload = new SearchPayload(false);
        $storedPayload->searchCondition = $this->getFactory()->getSerializer()->serialize($this->condition);
        $storedPayload->searchCode = 'S:title%3A+Symfony%3B';
        $storedPayload->exportedCondition = 'title: Symfony;';
        $storedPayload->exportedFormat = 'norm_string_query';
        $storedPayload->messages = [];

        $this->cache
            ->expects(self::once())
            ->method('set')
            ->with('f46ae6c70dbd72c18cf3d24623c30aedfc277831cc05f147d925ed26ebff54b1', $storedPayload);

        $payload = $this->createProcessor()->processRequest($request, $config);

        self::assertFalse($payload->isChanged());
        self::assertTrue($payload->isValid());
        self::assertEmpty($payload->messages);
        self::assertEquals($this->condition, $payload->searchCondition);
        self::assertEquals('norm_string_query', $payload->exportedFormat);
        self::assertEquals('title: Symfony;', $payload->exportedCondition);
        self::assertEquals('S:title%3A+Symfony%3B', $payload->searchCode);
    }

    public function testProcessSearchCodeFromQueryWithExistingCache()
    {
        // This condition would have given an transformer error,
        // so getting one means the transformer was never executed.
        $config = new ProcessorConfig($this->fieldSet, 'norm_string_query');
        $request = Request::create('/books', 'GET', ['search' => 'id: He;']);

        $storedPayload = new SearchPayload(false);
        $storedPayload->searchCondition = $this->getFactory()->getSerializer()->serialize($this->condition);
        $storedPayload->searchCode = 'S:id%3A+He%3B';
        $storedPayload->exportedCondition = 'id: He;';
        $storedPayload->exportedFormat = 'norm_string_query';
        $storedPayload->messages = [];

        $this->cache
            ->expects(self::once())
            ->method('get')
            ->with('130421eb7aa9a3528e139ea7477458edd5c5e838d3e33e24bbc96674982e2f0b')
            ->willReturn($storedPayload);

        $payload = $this->createProcessor()->processRequest($request, $config);

        self::assertFalse($payload->isChanged());
        self::assertTrue($payload->isValid());
        self::assertEmpty($payload->messages);
        self::assertEquals($this->condition, $payload->searchCondition);
        self::assertEquals('norm_string_query', $payload->exportedFormat);
        self::assertEquals('id: He;', $payload->exportedCondition);
        self::assertEquals('S:id%3A+He%3B', $payload->searchCode);

        $this->cache
            ->expects(self::never())
            ->method('set');
    }

    public function testProcessSearchCodeFromArrayQueryWithNoCache()
    {
        $config = new ProcessorConfig($this->fieldSet, 'norm_string_query');
        $request = Request::create('/books', 'GET', ['search' => ['fields' => ['title' => ['simple-values' => ['Symfony']]]]]);

        $this->cache
            ->expects(self::once())
            ->method('get')
            ->with('1fcbbcd37ad26675ca5825d4a9aa1d042fbda462350422b1e1178d9f985f2c3e')
            ->willReturn(null);

        $storedPayload = new SearchPayload(false);
        $storedPayload->searchCondition = $this->getFactory()->getSerializer()->serialize($this->condition);
        $storedPayload->searchCode = 'A:{"fields":{"title":{"simple-values":["Symfony"]}}}';
        $storedPayload->exportedCondition = ['fields' => ['title' => ['simple-values' => ['Symfony']]]];
        $storedPayload->exportedFormat = 'array';
        $storedPayload->messages = [];

        $this->cache
            ->expects(self::once())
            ->method('set')
            ->with('1fcbbcd37ad26675ca5825d4a9aa1d042fbda462350422b1e1178d9f985f2c3e', $storedPayload);

        $payload = $this->createProcessor()->processRequest($request, $config);

        self::assertFalse($payload->isChanged());
        self::assertTrue($payload->isValid());
        self::assertEmpty($payload->messages);
        self::assertEquals($this->condition, $payload->searchCondition);
        self::assertEquals('array', $payload->exportedFormat);
        self::assertEquals(['fields' => ['title' => ['simple-values' => ['Symfony']]]], $payload->exportedCondition);
        self::assertEquals('A:{"fields":{"title":{"simple-values":["Symfony"]}}}', $payload->searchCode);
    }

    public function testProcessSearchCodeFromArrayQueryWithExistingCache()
    {
        // This condition would have given an transformer error,
        // so getting one means the transformer was never executed.
        $config = new ProcessorConfig($this->fieldSet);
        $request = Request::create('/books', 'GET', ['search' => ['fields' => ['id' => ['simple-values' => ['He']]]]]);

        $storedPayload = new SearchPayload(false);
        $storedPayload->searchCondition = $this->getFactory()->getSerializer()->serialize($this->condition);
        $storedPayload->searchCode = 'A:{"fields":{"id":{"simple-values":["He"]}}}';
        $storedPayload->exportedCondition = ['fields' => ['id' => ['simple-values' => ['He']]]];
        $storedPayload->exportedFormat = 'array';
        $storedPayload->messages = [];

        $this->cache
            ->expects(self::once())
            ->method('get')
            ->with('17f8b815bf9d68deef251f9fd563d0b4b4da154901b6d3ffb082b5536f57fa7b')
            ->willReturn($storedPayload);

        $payload = $this->createProcessor()->processRequest($request, $config);

        self::assertFalse($payload->isChanged());
        self::assertTrue($payload->isValid());
        self::assertEmpty($payload->messages);
        self::assertEquals($this->condition, $payload->searchCondition);
        self::assertEquals('array', $payload->exportedFormat);
        self::assertEquals(['fields' => ['id' => ['simple-values' => ['He']]]], $payload->exportedCondition);
        self::assertEquals('A:{"fields":{"id":{"simple-values":["He"]}}}', $payload->searchCode);

        $this->cache
            ->expects(self::never())
            ->method('set');
    }

    public function testProcessSearchCodeWithInvalidCacheIsDeletedAndReProcessed()
    {
        $config = new ProcessorConfig($this->fieldSet);
        $request = Request::create('/books', 'GET', ['search' => 'title: Symfony;']);

        $storedPayload = new SearchPayload(false);
        $storedPayload->searchCondition = [$this->condition->getFieldSet()->getSetName(), 'invalid-I-am-I-am'];
        $storedPayload->searchCode = 'fields[id][simple-values][0]=He';
        $storedPayload->exportedCondition = ['fields' => ['id' => ['simple-values' => ['He']]]];
        $storedPayload->exportedFormat = 'norm_string_query';
        $storedPayload->messages = [];

        $this->cache
            ->expects(self::once())
            ->method('get')
            ->with('f46ae6c70dbd72c18cf3d24623c30aedfc277831cc05f147d925ed26ebff54b1')
            ->willReturn($storedPayload);

        $this->cache
            ->expects(self::once())
            ->method('delete')
            ->with('f46ae6c70dbd72c18cf3d24623c30aedfc277831cc05f147d925ed26ebff54b1');

        $payload = new SearchPayload();
        $payload->searchCondition = $this->condition;
        $payload->searchCode = 'S:title%3A+Symfony%3B';
        $payload->exportedCondition = 'title: Symfony;';
        $payload->exportedFormat = 'norm_string_query';
        $payload->messages = [];

        $newPayload = clone $payload;
        $newPayload->searchCondition = $this->getFactory()->getSerializer()->serialize($this->condition);

        $this->cache
            ->expects(self::once())
            ->method('set')
            ->with('f46ae6c70dbd72c18cf3d24623c30aedfc277831cc05f147d925ed26ebff54b1', $newPayload);

        $payload = $this->createProcessor()->processRequest($request, $config);

        self::assertFalse($payload->isChanged());
        self::assertTrue($payload->isValid());
        self::assertEmpty($payload->messages);
        self::assertEquals($this->condition, $payload->searchCondition);
        self::assertEquals('norm_string_query', $payload->exportedFormat);
        self::assertEquals('title: Symfony;', $payload->exportedCondition);
        self::assertEquals('S:title%3A+Symfony%3B', $payload->searchCode);
    }
}
