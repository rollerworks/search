<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests\Doctrine\Dbal;

use Rollerworks\Component\Search\Doctrine\Dbal\DoctrineDbalFactory;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\ValuesGroup;

class DoctrineDbalFactoryTest extends DbalTestCase
{
    /**
     * @var DoctrineDbalFactory
     */
    protected $factory;

    public function testCreateWhereBuilder()
    {
        $connection = $this->getConnectionMock();
        $searchCondition = new SearchCondition(new FieldSet('invoice'), new ValuesGroup());

        $whereBuilder = $this->factory->createWhereBuilder($connection, $searchCondition);

        $this->assertInstanceOf('Rollerworks\Component\Search\Doctrine\Dbal\WhereBuilder', $whereBuilder);
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

        $connection = $this->getConnectionMock();
        $searchCondition = new SearchCondition($fieldSet, new ValuesGroup());

        $whereBuilder = $this->factory->createWhereBuilder($connection, $searchCondition);

        $this->assertInstanceOf('Rollerworks\Component\Search\Doctrine\Dbal\WhereBuilder', $whereBuilder);
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

        $connection = $this->getConnectionMock();
        $searchCondition = new SearchCondition($fieldSet, new ValuesGroup());

        $whereBuilder = $this->factory->createWhereBuilder($connection, $searchCondition);

        $this->assertInstanceOf('Rollerworks\Component\Search\Doctrine\Dbal\WhereBuilder', $whereBuilder);
        $this->assertEquals(array('invoice_label' => $conversion), $whereBuilder->getValueConversions());
        $this->assertCount(0, $whereBuilder->getFieldConversions());
    }

    public function testCreateCacheWhereBuilder()
    {
        $connection = $this->getConnectionMock();
        $searchCondition = new SearchCondition(new FieldSet('invoice'), new ValuesGroup());

        $whereBuilder = $this->factory->createWhereBuilder($connection, $searchCondition);
        $this->assertInstanceOf('Rollerworks\Component\Search\Doctrine\Dbal\WhereBuilder', $whereBuilder);

        $cacheWhereBuilder = $this->factory->createCacheWhereBuilder($whereBuilder);
        $this->assertInstanceOf('Rollerworks\Component\Search\Doctrine\Dbal\CacheWhereBuilder', $cacheWhereBuilder);
    }

    protected function setUp()
    {
        parent::setUp();

        $cacheDriver = $this->getMock('Doctrine\Common\Cache\Cache');
        $this->factory = new DoctrineDbalFactory($cacheDriver);
    }
}
