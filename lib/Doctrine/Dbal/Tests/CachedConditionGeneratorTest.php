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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Type;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\SimpleCache\CacheInterface as Cache;
use Rollerworks\Component\Search\Doctrine\Dbal\CachedConditionGenerator;
use Rollerworks\Component\Search\Doctrine\Dbal\ConditionGenerator;
use Rollerworks\Component\Search\Doctrine\Dbal\Query\QueryField;
use Rollerworks\Component\Search\Doctrine\Dbal\SqlConditionGenerator;
use Rollerworks\Component\Search\GenericFieldSet;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\SearchConditionBuilder;
use Rollerworks\Component\Search\SearchPrimaryCondition;
use Rollerworks\Component\Search\Value\ValuesGroup;

final class CachedConditionGeneratorTest extends DbalTestCase
{
    /**
     * @var CachedConditionGenerator
     */
    private $cachedConditionGenerator;

    /**
     * @var MockObject
     */
    private $cacheDriver;

    /**
     * @var MockObject|SqlConditionGenerator
     */
    private $conditionGenerator;

    public function testGetWhereClauseNoCache()
    {
        $cacheKey = '';

        $this->cacheDriver
            ->expects(self::once())
            ->method('get')
            ->with(
                self::callback(function (string $key) use (&$cacheKey) {
                    $cacheKey = $key;

                    return true;
                })
            )
            ->willReturn(null);

        $this->conditionGenerator
            ->expects(self::once())
            ->method('getWhereClause')
            ->willReturn("me = 'foo'");

        $this->conditionGenerator
            ->expects(self::once())
            ->method('getParameters')
            ->willReturn($parameters = new ArrayCollection([':search' => [1, Type::getType('integer')]]));

        $this->cacheDriver
            ->expects(self::once())
            ->method('set')
            ->with(
                self::callback(
                    function (string $key) use (&$cacheKey) {
                        return $cacheKey === $key;
                    }
                ),
                ["me = 'foo'", [':search' => [1, 'integer']]],
                60
            );

        self::assertEquals("me = 'foo'", $this->cachedConditionGenerator->getWhereClause());
        self::assertEquals($parameters, $this->cachedConditionGenerator->getParameters());
    }

    public function testGetWhereClauseInvalidCache(): void
    {
        $cacheKey = '';

        $this->cacheDriver
            ->expects(self::once())
            ->method('get')
            ->with(
                self::callback(function (string $key) use (&$cacheKey) {
                    $cacheKey = $key;

                    return true;
                })
            )
            ->willReturn([]);

        $this->conditionGenerator
            ->expects(self::once())
            ->method('getWhereClause')
            ->willReturn("me = 'foo'");

        $this->conditionGenerator
            ->expects(self::once())
            ->method('getParameters')
            ->willReturn($parameters = new ArrayCollection([':search' => [1, Type::getType('integer')]]));

        $this->cacheDriver
            ->expects(self::once())
            ->method('set')
            ->with(
                self::callback(
                    function (string $key) use (&$cacheKey) {
                        return $cacheKey === $key;
                    }
                ),
                ["me = 'foo'", [':search' => [1, 'integer']]],
                60
            );

        self::assertEquals("me = 'foo'", $this->cachedConditionGenerator->getWhereClause());
        self::assertEquals($parameters, $this->cachedConditionGenerator->getParameters());
    }

    public function testGetWhereClauseWithCache()
    {
        $this->cacheDriver
            ->expects(self::once())
            ->method('get')
            ->with('7bdf48ca3ce581f79fe43359148b2b4f91934d3f2a7b542b1da034c5fdd057af')
            ->willReturn(["me = 'foo'", [':search' => [1, 'integer']]]);

        $this->conditionGenerator
            ->expects(self::never())
            ->method('getWhereClause');

        $this->conditionGenerator
            ->expects(self::never())
            ->method('getParameters');

        $this->cacheDriver
            ->expects(self::never())
            ->method('set');

        self::assertEquals("me = 'foo'", $this->cachedConditionGenerator->getWhereClause());
        self::assertEquals(new ArrayCollection([':search' => [1, Type::getType('integer')]]), $this->cachedConditionGenerator->getParameters());
    }

    public function testGetWhereWithPrepend()
    {
        $this->cacheDriver
            ->expects(self::once())
            ->method('get')
            ->with('7bdf48ca3ce581f79fe43359148b2b4f91934d3f2a7b542b1da034c5fdd057af')
            ->willReturn(["me = 'foo'", [':search' => [1, 'integer']]]);

        $this->conditionGenerator
            ->expects(self::never())
            ->method('getWhereClause');

        $this->conditionGenerator
            ->expects(self::never())
            ->method('getParameters');

        $this->cacheDriver
            ->expects(self::never())
            ->method('set');

        self::assertEquals("WHERE me = 'foo'", $this->cachedConditionGenerator->getWhereClause('WHERE '));
    }

    public function testGetEmptyWhereWithPrepend()
    {
        $this->cacheDriver
            ->expects(self::once())
            ->method('get')
            ->with('7bdf48ca3ce581f79fe43359148b2b4f91934d3f2a7b542b1da034c5fdd057af')
            ->willReturn(['', []]);

        $this->conditionGenerator
            ->expects(self::never())
            ->method('getWhereClause');

        $this->conditionGenerator
            ->expects(self::never())
            ->method('getParameters');

        $this->cacheDriver
            ->expects(self::never())
            ->method('set');

        self::assertEquals('', $this->cachedConditionGenerator->getWhereClause('WHERE '));
        self::assertEquals(new ArrayCollection(), $this->cachedConditionGenerator->getParameters());
    }

    public function testFieldMappingDelegation()
    {
        $cacheKey = '';

        $this->cacheDriver
            ->expects(self::once())
            ->method('get')
            ->with(
                self::callback(function (string $key) use (&$cacheKey) {
                    $cacheKey = $key;

                    return true;
                })
            )
            ->willReturn(null);

        $this->cacheDriver
            ->expects(self::once())
            ->method('set')
            ->with(
                self::callback(
                    function (string $key) use (&$cacheKey) {
                        return $cacheKey === $key;
                    }
                ),
                ['((I.id = :search_0))', [':search_0' => [18, 'integer']]],
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

        self::assertEquals('((I.id = :search_0))', $this->cachedConditionGenerator->getWhereClause());
        self::assertEquals(new ArrayCollection([':search_0' => [18, Type::getType('integer')]]), $this->cachedConditionGenerator->getParameters());
    }

    public function testGetWhereClauseCachedAndPrimaryCond()
    {
        $fieldSet = $this->getFieldSet();

        $cacheDriver = $this->prophesize(Cache::class);
        $cacheDriver->get('7503457faa505a978544359616a2b503638538170931ce460b69fcf35566f771')->willReturn(["me = 'foo'", [':search' => [1, 'integer']]]);
        $cacheDriver->get('65dc24cc06603327105d067e431b024f9dc0f7573db68fe839b6e244a821c4bb')->willReturn(["you = 'me' AND me = 'foo'", [':search' => [5, 'integer']]]);

        $cachedConditionGenerator = $this->createCachedConditionGenerator(
            $cacheDriver->reveal(),
            new SearchCondition($fieldSet, new ValuesGroup()),
            "me = 'foo'",
            $parameters = new ArrayCollection([':search' => [1, Type::getType('integer')]])
        );

        $searchCondition = new SearchCondition($fieldSet, new ValuesGroup());
        $searchCondition->setPrimaryCondition(new SearchPrimaryCondition(new ValuesGroup()));

        $cachedConditionGenerator2 = $this->createCachedConditionGenerator(
            $cacheDriver->reveal(),
            $searchCondition,
            "you = 'me' AND me = 'foo2'",
            $parameters2 = new ArrayCollection([':search' => [5, Type::getType('integer')]])
        );

        self::assertEquals("me = 'foo'", $cachedConditionGenerator->getWhereClause());
        self::assertEquals("you = 'me' AND me = 'foo'", $cachedConditionGenerator2->getWhereClause());

        self::assertEquals($parameters, $cachedConditionGenerator->getParameters());
        self::assertEquals($parameters2, $cachedConditionGenerator2->getParameters());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheDriver = $this->createMock(Cache::class);
        $this->conditionGenerator = $this->createMock(ConditionGenerator::class);

        $searchCondition = new SearchCondition(new GenericFieldSet([], 'invoice'), new ValuesGroup());

        $this->conditionGenerator->expects(self::any())->method('getSearchCondition')->willReturn($searchCondition);
        $this->cachedConditionGenerator = new CachedConditionGenerator($this->conditionGenerator, $this->cacheDriver, 60);
    }

    private function createCachedConditionGenerator(Cache $cacheDriver, SearchCondition $searchCondition, string $query, ArrayCollection $parameters): CachedConditionGenerator
    {
        $conditionGenerator = $this->prophesize(ConditionGenerator::class);
        $conditionGenerator->getWhereClause()->willReturn($query);
        $conditionGenerator->getFieldsMapping()->willReturn([
            'id' => [new QueryField('id', $searchCondition->getFieldSet()->get('id'), Type::getType('integer'), 'id', 'i')],
        ]);
        $conditionGenerator->getSearchCondition()->willReturn($searchCondition);

        return new CachedConditionGenerator($conditionGenerator->reveal(), $cacheDriver, 60);
    }
}
