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

namespace Rollerworks\Component\Search\Tests\Doctrine\Orm;

use Doctrine\ORM\Query\ResultSetMappingBuilder;
use Rollerworks\Component\Search\Doctrine\Orm\CachedDqlConditionGenerator;
use Rollerworks\Component\Search\Doctrine\Orm\CachedNativeQueryConditionGenerator;
use Rollerworks\Component\Search\Doctrine\Orm\DoctrineOrmFactory;
use Rollerworks\Component\Search\Doctrine\Orm\DqlConditionGenerator;
use Rollerworks\Component\Search\Doctrine\Orm\NativeQueryConditionGenerator;
use Rollerworks\Component\Search\GenericFieldSet;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\Value\ValuesGroup;

/**
 * @group non-functional
 */
class DoctrineOrmFactoryTest extends OrmTestCase
{
    /**
     * @var DoctrineOrmFactory
     */
    protected $factory;

    public function testCreateConditionGenerator()
    {
        $condition = new SearchCondition(new GenericFieldSet([]), new ValuesGroup());
        $query = $this->em->createQuery('SELECT I FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C WHERE ');

        $conditionGenerator = $this->factory->createConditionGenerator($query, $condition);
        $this->assertInstanceOf(DqlConditionGenerator::class, $conditionGenerator);
    }

    public function testCreateNativeConditionGenerator()
    {
        $rsm = new ResultSetMappingBuilder($this->em);
        $rsm->addRootEntityFromClassMetadata(
            'Rollerworks\Component\Search\Tests\Doctrine\Orm\Fixtures\Entity\ECommerceInvoice',
            'I',
            ['id' => 'invoice_id']
        );

        $rsm->addJoinedEntityFromClassMetadata(
            'Rollerworks\Component\Search\Tests\Doctrine\Orm\Fixtures\Entity\ECommerceCustomer',
            'C',
            'I',
            'customer',
            ['id' => 'customer_id']
        );

        $condition = new SearchCondition(new GenericFieldSet([]), new ValuesGroup());
        $query = $this->em->createNativeQuery(
            'SELECT I FROM Invoice I JOIN customer AS C ON I.customer = C.id',
            $rsm
        );

        $conditionGenerator = $this->factory->createConditionGenerator($query, $condition);
        $this->assertInstanceOf(NativeQueryConditionGenerator::class, $conditionGenerator);
    }

    public function testCachedDqlConditionGenerator()
    {
        $query = $this->em->createQuery('SELECT I FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C WHERE ');
        $searchCondition = new SearchCondition(new GenericFieldSet([]), new ValuesGroup());

        $conditionGenerator = $this->factory->createConditionGenerator($query, $searchCondition);
        $this->assertInstanceOf(DqlConditionGenerator::class, $conditionGenerator);

        $cachedConditionGenerator = $this->factory->createCachedConditionGenerator($conditionGenerator);
        $this->assertInstanceOf(CachedDqlConditionGenerator::class, $cachedConditionGenerator);
    }

    public function testCachedNativeQueryConditionGenerator()
    {
        $rsm = new ResultSetMappingBuilder($this->em);
        $rsm->addRootEntityFromClassMetadata(
            'Rollerworks\Component\Search\Tests\Doctrine\Orm\Fixtures\Entity\ECommerceInvoice',
            'I',
            ['id' => 'invoice_id']
        );

        $rsm->addJoinedEntityFromClassMetadata(
            'Rollerworks\Component\Search\Tests\Doctrine\Orm\Fixtures\Entity\ECommerceCustomer',
            'C',
            'I',
            'customer',
            ['id' => 'customer_id']
        );

        $condition = new SearchCondition(new GenericFieldSet([]), new ValuesGroup());
        $query = $this->em->createNativeQuery(
            'SELECT I FROM Invoice I JOIN customer AS C ON I.customer = C.id',
            $rsm
        );

        $conditionGenerator = $this->factory->createConditionGenerator($query, $condition);
        $this->assertInstanceOf(NativeQueryConditionGenerator::class, $conditionGenerator);

        $cachedConditionGenerator = $this->factory->createCachedConditionGenerator($conditionGenerator);
        $this->assertInstanceOf(CachedNativeQueryConditionGenerator::class, $cachedConditionGenerator);
    }

    // Missing Test for CachedNativeQueryConditionGenerator

    protected function setUp()
    {
        parent::setUp();

        $cacheDriver = $this->createMock(\Psr\SimpleCache\CacheInterface::class);
        $this->factory = new DoctrineOrmFactory($cacheDriver);
    }
}
