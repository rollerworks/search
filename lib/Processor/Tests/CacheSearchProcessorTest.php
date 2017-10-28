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

namespace Rollerworks\Component\Search\Processor\Tests;

use Psr\SimpleCache\CacheInterface;
use Rollerworks\Component\Search\ConditionErrorMessage;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\Processor\CachedSearchProcessor;
use Rollerworks\Component\Search\Processor\ProcessorConfig;
use Rollerworks\Component\Search\Processor\SearchPayload;
use Rollerworks\Component\Search\Processor\SearchProcessor;
use Rollerworks\Component\Search\Processor\Tests\Fixtures\FieldSet\UserFieldSet;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\SearchConditionBuilder;
use Rollerworks\Component\Search\Test\SearchIntegrationTestCase;
use Rollerworks\Component\Search\Value\ValuesBag;
use Rollerworks\Component\Search\Value\ValuesGroup;
use Zend\Diactoros\ServerRequest;

final class CacheSearchProcessorTest extends SearchIntegrationTestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|SearchProcessor
     */
    private $processor;

    /**
     * @var SearchCondition
     */
    private $condition;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|CacheInterface
     */
    private $cache;

    /**
     * @var FieldSet
     */
    private $fieldSet;

    protected function setUp()
    {
        parent::setUp();

        $this->processor = $this->createMock(SearchProcessor::class);
        $this->cache = $this->createMock(CacheInterface::class);

        $this->fieldSet = $this->getFactory()->createFieldSet(UserFieldSet::class);
        $this->condition = new SearchCondition(
            $this->fieldSet,
            (new ValuesGroup())->addField('name', (new ValuesBag())->addSimpleValue('user'))
        );
    }

    private function createProcessor(): CachedSearchProcessor
    {
        return new CachedSearchProcessor($this->cache, $this->processor, $this->getFactory());
    }

    public function testProcessingEmptyRequestIsValid()
    {
        $config = new ProcessorConfig($this->fieldSet);
        $request = new ServerRequest([], [], '/list');

        $this->expectProcessorNotExecuted();
        $this->cache
            ->expects(self::never())
            ->method('get');

        $this->cache
            ->expects(self::never())
            ->method('set');

        $payload = $this->createProcessor()->processRequest($request, $config);

        self::assertTrue($payload->isValid());
        self::assertEmpty($payload->messages);
        self::assertNull($payload->searchCondition);
        self::assertEmpty($payload->searchCode);
    }

    public function testProcessSearchCodeFromQueryWithNoCache()
    {
        $config = new ProcessorConfig($this->fieldSet);
        $request = (new ServerRequest([], [], '/list'))->withQueryParams(['search' => 'bmFtZTogdXNlcjs']);

        $this->cache
            ->expects(self::once())
            ->method('get')
            ->with('dfe9ee1facbc3548ab081e80f13535ecc02e12f94d74e02db3b2285cfcdbc955')
            ->willReturn(null);

        $payload = new SearchPayload();
        $payload->searchCondition = $this->condition;
        $payload->searchCode = 'bmFtZTogdXNlcjs';
        $payload->exportedCondition = 'name: user';
        $payload->messages = [];

        $storedPayload = new SearchPayload(false);
        $storedPayload->searchCondition = $this->getFactory()->getSerializer()->serialize($this->condition);
        $storedPayload->searchCode = 'bmFtZTogdXNlcjs';
        $storedPayload->exportedCondition = 'name: user';
        $storedPayload->messages = [];

        $this->cache
            ->expects(self::once())
            ->method('set')
            ->with('dfe9ee1facbc3548ab081e80f13535ecc02e12f94d74e02db3b2285cfcdbc955', $storedPayload);

        $this->processor
            ->expects(self::once())
            ->method('processRequest')
            ->with($request, $config)
            ->willReturn($payload);

        $payload = $this->createProcessor()->processRequest($request, $config);

        self::assertTrue($payload->isValid());
        self::assertEmpty($payload->messages);
        self::assertEquals($this->condition, $payload->searchCondition);
        self::assertEquals('bmFtZTogdXNlcjs', $payload->searchCode);
    }

    public function testProcessSearchCodeFromQueryWithCacheMismatch()
    {
        $config = new ProcessorConfig($this->fieldSet);
        $request = (new ServerRequest([], [], '/list'))->withQueryParams(['search' => 'bmFtZTogdXNlcjs~json']);

        $this->cache
            ->expects(self::once())
            ->method('get')
            ->with('674992d8edf814edd88bb315cb6c658681c3ddee974498cb17290ce75eab9c97')
            ->willReturn(null);

        $payload = new SearchPayload();
        $payload->searchCondition = $this->condition;
        $payload->searchCode = 'bmFtZTogdXNlcjs~json';
        $payload->exportedCondition = '{"fields":{"name":{"simple-values":["user"]}}}';
        $payload->messages = [];

        $storedPayload = new SearchPayload(false);
        $storedPayload->searchCondition = $this->getFactory()->getSerializer()->serialize($this->condition);
        $storedPayload->searchCode = 'bmFtZTogdXNlcjs~json';
        $storedPayload->exportedCondition = '{"fields":{"name":{"simple-values":["user"]}}}';
        $storedPayload->messages = [];

        $this->cache
            ->expects(self::once())
            ->method('set')
            ->with('674992d8edf814edd88bb315cb6c658681c3ddee974498cb17290ce75eab9c97', $storedPayload);

        $this->processor
            ->expects(self::once())
            ->method('processRequest')
            ->with($request, $config)
            ->willReturn($payload);

        $payload = $this->createProcessor()->processRequest($request, $config);

        self::assertTrue($payload->isValid());
        self::assertEmpty($payload->messages);
        self::assertEquals($this->condition, $payload->searchCondition);
        self::assertEquals('bmFtZTogdXNlcjs~json', $payload->searchCode);
    }

    public function testProcessSearchCodeFromQueryWithExistingCache()
    {
        $config = new ProcessorConfig($this->fieldSet);
        $request = (new ServerRequest([], [], '/list'))->withQueryParams(['search' => 'bmFtZTogdXNlcjs']);

        $storedPayload = new SearchPayload(false);
        $storedPayload->searchCondition = $this->getFactory()->getSerializer()->serialize($this->condition);
        $storedPayload->searchCode = 'bmFtZTogdXNlcjs';
        $storedPayload->exportedCondition = 'name: user';
        $storedPayload->messages = [];

        $this->cache
            ->expects(self::once())
            ->method('get')
            ->with('dfe9ee1facbc3548ab081e80f13535ecc02e12f94d74e02db3b2285cfcdbc955')
            ->willReturn($storedPayload);

        $this->expectProcessorNotExecuted();

        $payload = $this->createProcessor()->processRequest($request, $config);

        self::assertTrue($payload->isValid());
        self::assertEmpty($payload->messages);
        self::assertEquals($this->condition, $payload->searchCondition);
        self::assertEquals('name: user', $payload->exportedCondition);
        self::assertEquals('bmFtZTogdXNlcjs', $payload->searchCode);

        $this->cache
            ->expects(self::never())
            ->method('set');
    }

    public function testProcessSearchCodeWithInvalidCacheIsDeletedAndGivesEmpty()
    {
        $config = new ProcessorConfig($this->fieldSet);
        $request = (new ServerRequest([], [], '/list'))->withQueryParams(['search' => 'bmFtZTogdXNlcjs']);

        $storedPayload = new SearchPayload(false);
        $storedPayload->searchCondition = [$this->condition->getFieldSet()->getSetName(), 'invalid-I-am-I-am'];
        $storedPayload->searchCode = 'bmFtZTogdXNlcjs';
        $storedPayload->exportedCondition = 'name: user';
        $storedPayload->messages = [];

        $this->cache
            ->expects(self::once())
            ->method('get')
            ->with('dfe9ee1facbc3548ab081e80f13535ecc02e12f94d74e02db3b2285cfcdbc955')
            ->willReturn($storedPayload);

        $this->cache
            ->expects(self::once())
            ->method('delete')
            ->with('dfe9ee1facbc3548ab081e80f13535ecc02e12f94d74e02db3b2285cfcdbc955');

        $payload = new SearchPayload();
        $payload->searchCondition = $this->condition;
        $payload->searchCode = 'bmFtZTogdXNlcjs~json';
        $payload->exportedCondition = '{"fields":{"name":{"simple-values":["user"]}}}';
        $payload->messages = [];

        $this->processor
            ->expects(self::once())
            ->method('processRequest')
            ->with($request, $config)
            ->willReturn($payload);

        $newPayload = clone $payload;
        $newPayload->searchCondition = $this->getFactory()->getSerializer()->serialize($this->condition);

        $this->cache
            ->expects(self::once())
            ->method('set')
            ->with('674992d8edf814edd88bb315cb6c658681c3ddee974498cb17290ce75eab9c97', $newPayload);

        $payload = $this->createProcessor()->processRequest($request, $config);

        self::assertFalse($payload->isChanged());
        self::assertTrue($payload->isValid());
        self::assertEmpty($payload->messages);
        self::assertEquals($this->condition, $payload->searchCondition);
        self::assertEquals('{"fields":{"name":{"simple-values":["user"]}}}', $payload->exportedCondition);
        self::assertEquals('bmFtZTogdXNlcjs~json', $payload->searchCode);
    }

    public function testProcessSearchCodeFromPost()
    {
        $this->condition = SearchConditionBuilder::create($this->fieldSet)
                ->field('name')
                    ->addSimpleValue('yoda')
                ->end()
            ->getSearchCondition()
        ;

        $config = new ProcessorConfig($this->fieldSet);
        $request = (new ServerRequest([], [], '/list', 'POST'))->withParsedBody(['search' => 'name: yoda;']);

        $this->cache
            ->expects(self::never())
            ->method('get');

        $payload = new SearchPayload(true);
        $payload->searchCondition = $this->condition;
        $payload->searchCode = 'bmFtZTogeW9kYTs~string_query';
        $payload->exportedCondition = 'name: yoda;';
        $payload->messages = [];

        $storedPayload = clone $payload;
        $storedPayload->changed = false;
        $storedPayload->searchCondition = $this->getFactory()->getSerializer()->serialize($this->condition);

        $this->cache
            ->expects(self::once())
            ->method('set')
            ->with('ba74d6f74b2821d17694ba5280c4faad086b78f61f8e98a2c0a0c2902acde54a', $storedPayload);

        $this->processor
            ->expects(self::once())
            ->method('processRequest')
            ->with($request, $config)
            ->willReturn($payload);

        $payload = $this->createProcessor()->processRequest($request, $config);

        self::assertTrue($payload->isChanged());
        self::assertTrue($payload->isValid());
        self::assertEmpty($payload->messages);
        self::assertEquals($this->condition, $payload->searchCondition);
        self::assertEquals('bmFtZTogeW9kYTs~string_query', $payload->searchCode);
    }

    public function testProcessSearchCodeFromPostWithInvalidValuesIsNotStored()
    {
        $config = new ProcessorConfig($this->fieldSet);
        $request = (new ServerRequest([], [], '/list', 'POST'))->withParsedBody(['search' => 'name: user;']);

        $this->cache
            ->expects(self::never())
            ->method('get');

        $this->cache
            ->expects(self::never())
            ->method('set');

        $payload = new SearchPayload(true);
        $payload->searchCondition = null;
        $payload->exportedCondition = null;
        $payload->messages = $errorList = [ConditionErrorMessage::rawMessage('root', 'We have a problem')];
        $payload->searchCode = '';

        $this->processor
            ->expects(self::once())
            ->method('processRequest')
            ->with($request, $config)
            ->willReturn($payload);

        $payload = $this->createProcessor()->processRequest($request, $config);

        self::assertTrue($payload->isChanged());
        self::assertFalse($payload->isValid());
        self::assertEquals($errorList, $payload->messages);
        self::assertNull($payload->searchCondition);
        self::assertNull($payload->exportedCondition);
        self::assertEquals('', $payload->searchCode);
    }

    public function testDoesNotUseSearchCodeWhenPostIsUsed()
    {
        $config = new ProcessorConfig($this->fieldSet);
        $request = (new ServerRequest([], [], '/list', 'POST'))
            ->withQueryParams(['search' => 'bmFtZTogdXNlcjs'])
            ->withParsedBody(['search' => 'name: user;']);

        $this->cache
            ->expects(self::never())
            ->method('get');

        $this->cache
            ->expects(self::never())
            ->method('set');

        $payload = new SearchPayload(true);
        $payload->searchCondition = null;
        $payload->exportedCondition = null;
        $payload->messages = $errorList = [ConditionErrorMessage::rawMessage('root', 'We have a problem')];
        $payload->searchCode = '';

        $this->processor
            ->expects(self::once())
            ->method('processRequest')
            ->with($request, $config)
            ->willReturn($payload);

        $payload = $this->createProcessor()->processRequest($request, $config);

        self::assertTrue($payload->isChanged());
        self::assertFalse($payload->isValid());
        self::assertNull($payload->searchCondition);
        self::assertNull($payload->exportedCondition);
        self::assertEquals('', $payload->searchCode);
    }

    private function expectProcessorNotExecuted(): void
    {
        $this->processor
            ->expects(self::never())
            ->method('processRequest');
    }
}
