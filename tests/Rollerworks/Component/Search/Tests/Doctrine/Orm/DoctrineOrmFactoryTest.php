<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search\Tests\Doctrine\Orm;

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
        $query = $this->em->createQuery('SELECT I FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C WHERE ');
        $searchCondition = new SearchCondition(new FieldSet('invoice'), new ValuesGroup());

        $whereBuilder = $this->factory->createWhereBuilder($query, $searchCondition);

        $this->assertInstanceOf('Rollerworks\Component\Search\Doctrine\Orm\WhereBuilder', $whereBuilder);
    }

    public function testCreateWhereBuilderWithConversionSetting()
    {
        $fieldSet = new FieldSet('invoice');

        $conversion = $this->getMock('Rollerworks\Component\Search\Doctrine\Dbal\ValueConversionInterface');

        $fieldLabel = $this->getMock('Rollerworks\Component\Search\FieldConfigInterface');
        $fieldLabel->expects($this->once())->method('hasOption')->with('doctrine_dbal_conversion')->will($this->returnValue(true));
        $fieldLabel->expects($this->once())->method('getOption')->with('doctrine_dbal_conversion')->will($this->returnValue($conversion));
        $fieldSet->set('invoice_label', $fieldLabel);

        $fieldCustomer = $this->getMock('Rollerworks\Component\Search\FieldConfigInterface');
        $fieldCustomer->expects($this->once())->method('hasOption')->with('doctrine_dbal_conversion')->will($this->returnValue(false));
        $fieldCustomer->expects($this->never())->method('getOption');
        $fieldSet->set('invoice_customer', $fieldCustomer);

        $query = $this->em->createQuery('SELECT I FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C WHERE ');
        $searchCondition = new SearchCondition($fieldSet, new ValuesGroup());

        $whereBuilder = $this->factory->createWhereBuilder($query, $searchCondition);

        $this->assertInstanceOf('Rollerworks\Component\Search\Doctrine\Orm\WhereBuilder', $whereBuilder);
        $this->assertEquals(array('invoice_label' => $conversion), $whereBuilder->getValueConversions());
        $this->assertCount(0, $whereBuilder->getFieldConversions());
    }

    public function testCreateWhereBuilderWithLazyConversionSetting()
    {
        $fieldSet = new FieldSet('invoice');

        $test = $this;
        $conversion = $test->getMock('Rollerworks\Component\Search\Doctrine\Dbal\ValueConversionInterface');
        $lazyConversion = function () use ($conversion) {
            return $conversion;
        };

        $fieldLabel = $this->getMock('Rollerworks\Component\Search\FieldConfigInterface');
        $fieldLabel->expects($this->once())->method('hasOption')->with('doctrine_dbal_conversion')->will($this->returnValue(true));
        $fieldLabel->expects($this->once())->method('getOption')->with('doctrine_dbal_conversion')->will($this->returnValue($lazyConversion));
        $fieldSet->set('invoice_label', $fieldLabel);

        $fieldCustomer = $this->getMock('Rollerworks\Component\Search\FieldConfigInterface');
        $fieldCustomer->expects($this->once())->method('hasOption')->with('doctrine_dbal_conversion')->will($this->returnValue(false));
        $fieldCustomer->expects($this->never())->method('getOption');
        $fieldSet->set('invoice_customer', $fieldCustomer);

        $query = $this->em->createQuery('SELECT I FROM Rollerworks\Component\Search\Tests\Fixtures\Entity\ECommerceInvoice I JOIN I.customer C WHERE ');
        $searchCondition = new SearchCondition($fieldSet, new ValuesGroup());

        $whereBuilder = $this->factory->createWhereBuilder($query, $searchCondition);

        $this->assertInstanceOf('Rollerworks\Component\Search\Doctrine\Orm\WhereBuilder', $whereBuilder);
        $this->assertEquals(array('invoice_label' => $conversion), $whereBuilder->getValueConversions());
        $this->assertCount(0, $whereBuilder->getFieldConversions());
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

        $cacheDriver = $this->getMock('Doctrine\Common\Cache\Cache');
        $this->factory = new DoctrineOrmFactory($cacheDriver);
    }
}
