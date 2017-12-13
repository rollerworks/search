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

namespace Rollerworks\Component\Search\Tests\Doctrine\Dbal;

use Doctrine\DBAL\Types\Type;
use Psr\SimpleCache\CacheInterface as Cache;
use Rollerworks\Component\Search\Doctrine\Dbal\CachedConditionGenerator;
use Rollerworks\Component\Search\Doctrine\Dbal\ConditionGenerator;
use Rollerworks\Component\Search\Doctrine\Dbal\Query\QueryField;
use Rollerworks\Component\Search\Doctrine\Dbal\SqlConditionGenerator;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\GenericFieldSet;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\SearchConditionBuilder;
use Rollerworks\Component\Search\SearchPreCondition;
use Rollerworks\Component\Search\Value\ValuesGroup;

final class CachedConditionGeneratorTest extends DbalTestCase
{
    /**
     * @var CachedConditionGenerator
     */
    private $cachedConditionGenerator;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $cacheDriver;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $conditionGenerator;

    public function testGetWhereClauseNoCache()
    {
        $cacheKey = '';

        $this->cacheDriver
            ->expects($this->once())
            ->method('has')
            ->with(
                self::callback(function (string $key) use (&$cacheKey) {
                    $cacheKey = $key;

                    return true;
                })
            )
            ->willReturn(false);

        $this->cacheDriver
            ->expects($this->never())
            ->method('get');

        $this->conditionGenerator
            ->expects($this->once())
            ->method('getWhereClause')
            ->willReturn("me = 'foo'");

        $this->cacheDriver
            ->expects($this->once())
            ->method('set')
            ->with(
                self::callback(
                    function (string $key) use (&$cacheKey) {
                        return $cacheKey === $key;
                    }
                ),
                "me = 'foo'",
                60
            );

        self::assertEquals("me = 'foo'", $this->cachedConditionGenerator->getWhereClause());
    }

    public function testGetWhereClauseWithCache()
    {
        $cacheKey = '';

        $this->cacheDriver
            ->expects(self::once())
            ->method('has')
            ->with(
                self::callback(function (string $key) use (&$cacheKey) {
                    $cacheKey = $key;

                    return true;
                })
            )
            ->willReturn(true);

        $this->cacheDriver
            ->expects(self::once())
            ->method('get')
            ->with(
                self::callback(function (string $key) use (&$cacheKey) {
                    return $cacheKey === $key;
                })
            )
            ->willReturn("me = 'foo'");

        $this->conditionGenerator
            ->expects(self::never())
            ->method('getWhereClause');

        $this->cacheDriver
            ->expects(self::never())
            ->method('set');

        self::assertEquals("me = 'foo'", $this->cachedConditionGenerator->getWhereClause());
    }

    public function testGetWhereWithPrepend()
    {
        $cacheKey = '';

        $this->cacheDriver
            ->expects(self::once())
            ->method('has')
            ->with(
                self::callback(function (string $key) use (&$cacheKey) {
                    $cacheKey = $key;

                    return true;
                })
            )
            ->willReturn(true);

        $this->cacheDriver
            ->expects(self::once())
            ->method('get')
            ->with(
                self::callback(function (string $key) use (&$cacheKey) {
                    return $cacheKey === $key;
                })
            )
            ->willReturn("me = 'foo'");

        $this->conditionGenerator
            ->expects(self::never())
            ->method('getWhereClause');

        $this->cacheDriver
            ->expects(self::never())
            ->method('set');

        self::assertEquals("WHERE me = 'foo'", $this->cachedConditionGenerator->getWhereClause('WHERE '));
    }

    public function testGetEmptyWhereWithPrepend()
    {
        $this->cacheDriver
            ->expects(self::once())
            ->method('has')
            ->with(
                self::callback(function (string $key) use (&$cacheKey) {
                    $cacheKey = $key;

                    return true;
                })
            )
            ->willReturn(true);

        $this->cacheDriver
            ->expects(self::once())
            ->method('get')
            ->with(
                self::callback(function (string $key) use (&$cacheKey) {
                    return $cacheKey === $key;
                })
            )
            ->willReturn('');

        $this->conditionGenerator
            ->expects(self::never())
            ->method('getWhereClause');

        $this->cacheDriver
            ->expects(self::never())
            ->method('set');

        self::assertEquals('', $this->cachedConditionGenerator->getWhereClause('WHERE '));
    }

    public function testFieldMappingDelegation()
    {
        $cacheKey = '';

        $this->cacheDriver
            ->expects($this->once())
            ->method('has')
            ->with(
                self::callback(function (string $key) use (&$cacheKey) {
                    $cacheKey = $key;

                    return true;
                })
            )
            ->willReturn(false);

        $this->cacheDriver
            ->expects($this->never())
            ->method('get');

        $this->cacheDriver
            ->expects($this->once())
            ->method('set')
            ->with(
                self::callback(
                    function (string $key) use (&$cacheKey) {
                        return $cacheKey === $key;
                    }
                ),
                '((I.id IN(18)))',
                60
            );

        $searchCondition = SearchConditionBuilder::create($this->getFieldSet())
            ->field('customer')
                ->addSimpleValue(18)
            ->end()
        ->getSearchCondition();

        $this->conditionGenerator = new SqlConditionGenerator($this->getConnectionMock(), $searchCondition);

        $this->cachedConditionGenerator = new CachedConditionGenerator($this->conditionGenerator, $this->cacheDriver, 60);
        $this->cachedConditionGenerator->setField('customer', 'id', 'I', 'integer');

        self::assertEquals('((I.id IN(18)))', $this->cachedConditionGenerator->getWhereClause());
    }

    public function testGetWhereClauseCachedAndPreCond()
    {
        $fieldSet = $this->getFieldSet();

        $cacheDriver = $this->prophesize(Cache::class);
        $cacheDriver->has('7503457faa505a978544359616a2b503638538170931ce460b69fcf35566f771')->willReturn(true);
        $cacheDriver->get('7503457faa505a978544359616a2b503638538170931ce460b69fcf35566f771')->willReturn("me = 'foo'");

        $cacheDriver->has('cb136884ef8b94935e3df27498f8882cce67a40ba5f6e6eb30ba7c6b4db65841')->willReturn(true);
        $cacheDriver->get('cb136884ef8b94935e3df27498f8882cce67a40ba5f6e6eb30ba7c6b4db65841')->willReturn("you = 'me' AND me = 'foo'");

        $cachedConditionGenerator = $this->createCachedConditionGenerator(
            $cacheDriver->reveal(),
            new SearchCondition($fieldSet, new ValuesGroup()),
            "me = 'foo'"
        );

        $searchCondition = new SearchCondition($fieldSet, new ValuesGroup());
        $searchCondition->setPreCondition(new SearchPreCondition(new ValuesGroup()));

        $cachedConditionGenerator2 = $this->createCachedConditionGenerator(
            $cacheDriver->reveal(),
            $searchCondition,
            "you = 'me' AND me = 'foo2'"
        );

        self::assertEquals("me = 'foo'", $cachedConditionGenerator->getWhereClause());
        self::assertEquals("you = 'me' AND me = 'foo'", $cachedConditionGenerator2->getWhereClause());
    }

    protected function setUp()
    {
        parent::setUp();

        $this->cacheDriver = $this->createMock(Cache::class);
        $this->conditionGenerator = $this->createMock(ConditionGenerator::class);

        $searchCondition = new SearchCondition(new GenericFieldSet([], 'invoice'), new ValuesGroup());

        $this->conditionGenerator->expects(self::any())->method('getSearchCondition')->willReturn($searchCondition);
        $this->cachedConditionGenerator = new CachedConditionGenerator($this->conditionGenerator, $this->cacheDriver, 60);
    }

    private function createCachedConditionGenerator(Cache $cacheDriver, SearchCondition $searchCondition, string $query): CachedConditionGenerator
    {
        $conditionGenerator = $this->prophesize(ConditionGenerator::class);
        $conditionGenerator->getWhereClause()->willReturn($query);
        $conditionGenerator->getFieldsMapping()->willReturn([
            'id' => [new QueryField('id', $searchCondition->getFieldSet()->get('id'), Type::getType('integer'), 'id', 'i')]
        ]);
        $conditionGenerator->getSearchCondition()->willReturn($searchCondition);

        return new CachedConditionGenerator($conditionGenerator->reveal(), $cacheDriver, 60);
    }
}
