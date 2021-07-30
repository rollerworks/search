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

/**
 * @internal
 */
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

    /** @test */
    public function get_where_clause_no_cache(): void
    {
        $cacheKey = '';

        $this->cacheDriver
            ->expects(self::once())
            ->method('get')
            ->with(
                self::callback(static function (string $key) use (&$cacheKey) {
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
                    static function (string $key) use (&$cacheKey) {
                        return $cacheKey === $key;
                    }
                ),
                ["me = 'foo'", [':search' => [1, 'integer']]],
                60
            );

        self::assertEquals("me = 'foo'", $this->cachedConditionGenerator->getWhereClause());
        self::assertEquals($parameters, $this->cachedConditionGenerator->getParameters());
    }

    /** @test */
    public function get_where_clause_invalid_cache(): void
    {
        $cacheKey = '';

        $this->cacheDriver
            ->expects(self::once())
            ->method('get')
            ->with(
                self::callback(static function (string $key) use (&$cacheKey) {
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
                    static function (string $key) use (&$cacheKey) {
                        return $cacheKey === $key;
                    }
                ),
                ["me = 'foo'", [':search' => [1, 'integer']]],
                60
            );

        self::assertEquals("me = 'foo'", $this->cachedConditionGenerator->getWhereClause());
        self::assertEquals($parameters, $this->cachedConditionGenerator->getParameters());
    }

    /** @test */
    public function get_where_clause_with_cache(): void
    {
        $this->cacheDriver
            ->expects(self::once())
            ->method('get')
            ->with('62e186fb1789cc8fd59315f3453808771910dae798440eee8b85d83889d5e88a')
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

    /** @test */
    public function get_where_with_prepend(): void
    {
        $this->cacheDriver
            ->expects(self::once())
            ->method('get')
            ->with('62e186fb1789cc8fd59315f3453808771910dae798440eee8b85d83889d5e88a')
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

    /** @test */
    public function get_empty_where_with_prepend(): void
    {
        $this->cacheDriver
            ->expects(self::once())
            ->method('get')
            ->with('62e186fb1789cc8fd59315f3453808771910dae798440eee8b85d83889d5e88a')
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

    /** @test */
    public function field_mapping_delegation(): void
    {
        $cacheKey = '';

        $this->cacheDriver
            ->expects(self::once())
            ->method('get')
            ->with(
                self::callback(static function (string $key) use (&$cacheKey) {
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
                    static function (string $key) use (&$cacheKey) {
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

    /** @test */
    public function get_where_clause_cached_and_primary_cond(): void
    {
        $fieldSet = $this->getFieldSet();

        $cacheDriver = $this->prophesize(Cache::class);
        $cacheDriver->get('cb991a892faabc87fd36502af520e0e1fad70617cf4d11a5dc8ca8feb9417235')->willReturn(["me = 'foo'", [':search' => [1, 'integer']]]);
        $cacheDriver->get('aac1029ef411d16c316398274bb01cdad21999c91d6552f6a5afa2a399094415')->willReturn(["you = 'me' AND me = 'foo'", [':search' => [5, 'integer']]]);

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
