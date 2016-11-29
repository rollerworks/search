<?php

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
use Rollerworks\Component\Search\Doctrine\Orm\DoctrineOrmFactory;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\ValuesGroup;

class DoctrineOrmFactoryTest extends OrmTestCase
{
    /**
     * @var DoctrineOrmFactory
     */
    protected $factory;

    public function testCreateWhereBuilder()
    {
        $condition = new SearchCondition(new FieldSet('invoice'), new ValuesGroup());
        $query = $this->em->createQuery('SELECT I FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C WHERE ');

        $whereBuilder = $this->factory->createWhereBuilder($query, $condition);
        $this->assertInstanceOf('Rollerworks\Component\Search\Doctrine\Orm\WhereBuilder', $whereBuilder);
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

        $condition = new SearchCondition(new FieldSet('invoice'), new ValuesGroup());
        $query = $this->em->createNativeQuery(
            'SELECT I FROM Invoice I JOIN customer AS C ON I.customer = C.id',
            $rsm
        );

        $whereBuilder = $this->factory->createWhereBuilder($query, $condition);
        $this->assertInstanceOf('Rollerworks\Component\Search\Doctrine\Orm\NativeWhereBuilder', $whereBuilder);
    }

    public function testCreateWhereBuilderWithConversionSetting()
    {
        $invoiceClass = 'Rollerworks\Component\Search\Tests\Doctrine\Orm\Fixtures\Entity\ECommerceInvoice';
        $conversion = $this->getMockBuilder('Rollerworks\Component\Search\Doctrine\Dbal\ValueConversionInterface')->getMock();

        $fieldSet = $this->getFieldSet(false);
        $fieldSet->add('label', 'invoice_label', ['doctrine_dbal_conversion' => $conversion], false, $invoiceClass, 'label');
        $fieldSet = $fieldSet->getFieldSet();

        $query = $this->em->createQuery(
            'SELECT I FROM Rollerworks\Component\Search\Tests\Doctrine\Orm\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C'
        );

        $searchCondition = new SearchCondition($fieldSet, new ValuesGroup());
        $whereBuilder = $this->factory->createWhereBuilder($query, $searchCondition);
        $whereBuilder->setEntityMapping('Rollerworks\Component\Search\Tests\Doctrine\Orm\Fixtures\Entity\ECommerceInvoice', 'I');
        $whereBuilder->setEntityMapping('Rollerworks\Component\Search\Tests\Doctrine\Orm\Fixtures\Entity\ECommerceCustomer', 'C');

        $this->assertInstanceOf('Rollerworks\Component\Search\Doctrine\Orm\WhereBuilder', $whereBuilder);
        $this->assertEquals($conversion, $whereBuilder->getFieldsConfig()->getFields()['label']->getValueConversion());
        $this->assertNull($whereBuilder->getFieldsConfig()->getFields()['id']->getValueConversion());
    }

    public function testCreateWhereBuilderWithLazyConversionSetting()
    {
        $conversion = $this->getMockBuilder('Rollerworks\Component\Search\Doctrine\Dbal\ValueConversionInterface')->getMock();
        $lazyConversion = function () use ($conversion) {
            return $conversion;
        };

        $invoiceClass = 'Rollerworks\Component\Search\Tests\Doctrine\Orm\Fixtures\Entity\ECommerceInvoice';
        $conversion = $this->getMockBuilder('Rollerworks\Component\Search\Doctrine\Dbal\ValueConversionInterface')->getMock();

        $fieldSet = $this->getFieldSet(false);
        $fieldSet->add('label', 'invoice_label', ['doctrine_dbal_conversion' => $lazyConversion], false, $invoiceClass, 'label');
        $fieldSet = $fieldSet->getFieldSet();

        $query = $this->em->createQuery(
            'SELECT I FROM Rollerworks\Component\Search\Tests\Doctrine\Orm\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C'
        );

        $searchCondition = new SearchCondition($fieldSet, new ValuesGroup());
        $whereBuilder = $this->factory->createWhereBuilder($query, $searchCondition);
        $whereBuilder->setEntityMapping('Rollerworks\Component\Search\Tests\Doctrine\Orm\Fixtures\Entity\ECommerceInvoice', 'I');
        $whereBuilder->setEntityMapping('Rollerworks\Component\Search\Tests\Doctrine\Orm\Fixtures\Entity\ECommerceCustomer', 'C');

        $this->assertInstanceOf('Rollerworks\Component\Search\Doctrine\Orm\WhereBuilder', $whereBuilder);
        $this->assertEquals($conversion, $whereBuilder->getFieldsConfig()->getFields()['label']->getValueConversion());
        $this->assertNull($whereBuilder->getFieldsConfig()->getFields()['id']->getValueConversion());
    }

    public function testCreateCacheWhereBuilder()
    {
        $query = $this->em->createQuery('SELECT I FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C WHERE ');
        $searchCondition = new SearchCondition(new FieldSet('invoice'), new ValuesGroup());

        $whereBuilder = $this->factory->createWhereBuilder($query, $searchCondition);
        $this->assertInstanceOf('Rollerworks\Component\Search\Doctrine\Orm\WhereBuilder', $whereBuilder);

        $cacheWhereBuilder = $this->factory->createCacheWhereBuilder($whereBuilder);
        $this->assertInstanceOf('Rollerworks\Component\Search\Doctrine\Orm\CacheWhereBuilder', $cacheWhereBuilder);
    }

    protected function setUp()
    {
        parent::setUp();

        $cacheDriver = $this->getMockBuilder('Doctrine\Common\Cache\Cache')->getMock();
        $this->factory = new DoctrineOrmFactory($cacheDriver);
    }
}
