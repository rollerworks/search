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
use Rollerworks\Component\Search\Doctrine\Orm\CacheWhereBuilder;
use Rollerworks\Component\Search\Doctrine\Orm\DoctrineOrmFactory;
use Rollerworks\Component\Search\Doctrine\Orm\NativeWhereBuilder;
use Rollerworks\Component\Search\Doctrine\Orm\WhereBuilder;
use Rollerworks\Component\Search\GenericFieldSet;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\Value\ValuesGroup;

class DoctrineOrmFactoryTest extends OrmTestCase
{
    /**
     * @var DoctrineOrmFactory
     */
    protected $factory;

    public function testCreateWhereBuilder()
    {
        $condition = new SearchCondition(new GenericFieldSet([]), new ValuesGroup());
        $query = $this->em->createQuery('SELECT I FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C WHERE ');

        $whereBuilder = $this->factory->createWhereBuilder($query, $condition);
        $this->assertInstanceOf(WhereBuilder::class, $whereBuilder);
    }

    public function testCreateNativeWhereBuilder()
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

        $whereBuilder = $this->factory->createWhereBuilder($query, $condition);
        $this->assertInstanceOf(NativeWhereBuilder::class, $whereBuilder);
    }

    public function testCreateCacheWhereBuilder()
    {
        $query = $this->em->createQuery('SELECT I FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C WHERE ');
        $searchCondition = new SearchCondition(new GenericFieldSet([]), new ValuesGroup());

        $whereBuilder = $this->factory->createWhereBuilder($query, $searchCondition);
        $this->assertInstanceOf(WhereBuilder::class, $whereBuilder);

        $cacheWhereBuilder = $this->factory->createCacheWhereBuilder($whereBuilder);
        $this->assertInstanceOf(CacheWhereBuilder::class, $cacheWhereBuilder);
    }

    protected function setUp()
    {
        parent::setUp();

        $cacheDriver = $this->getMockBuilder('Doctrine\Common\Cache\Cache')->getMock();
        $this->factory = new DoctrineOrmFactory($cacheDriver);
    }
}
