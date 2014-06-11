<?php

/**
 * This file is part of RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Extension\Doctrine\Orm\Conversion;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Rollerworks\Component\Search\Extension\Doctrine\Dbal\DoctrineDbalExtension;
use Rollerworks\Component\Search\Extension\Doctrine\Orm\DoctrineOrmExtension;
use Rollerworks\Component\Search\Test\SearchIntegrationTestCase;

class EntityCountConversionTest extends SearchIntegrationTestCase
{
    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    private $emRegistry;

    /**
     * @var EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $em;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $config;

    protected function setUp()
    {
        $this->config = $this->getMock('Doctrine\ORM\Configuration');
        $this->config->expects($this->atLeastOnce())->method('addCustomStringFunction');
        $this->em = $this->getMock('Doctrine\ORM\EntityManagerInterface');
        $this->em->expects($this->atLeastOnce())->method('getConfiguration')->will($this->returnValue($this->config));
        $this->emRegistry = $this->createRegistryMock('default', $this->em);

        parent::setUp();
    }

    public function testConvertSqlField()
    {
        $field = $this->factory->createFieldForProperty('Invoice', 'children', 'children', 'doctrine_orm_entity_count');
        $hints = array(
            'search_field' => $field,
            'entity_manager' => $this->em,
        );

        $conversion = new EntityCountConversion();
        $output = $conversion->convertSqlField('i.id', array('table_name' => 'invoices', 'table_field' => 'children'), $hints);

        $this->assertEquals("(SELECT COUNT(*) FROM invoices WHERE children = i.id)", $output);
    }

    public function testConvertSqlFieldWithAutoResolver()
    {
        $field = $this->factory->createFieldForProperty('Invoice', 'children', 'children', 'doctrine_orm_entity_count');
        $hints = array(
            'search_field' => $field,
            'entity_manager' => $this->em,
        );

        $metaMock = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')->disableOriginalConstructor()->getMock();
        $metaMock->expects($this->atLeastOnce())
            ->method('getAssociationTargetClass')
            ->with($this->equalTo('children'))
            ->will($this->returnValue('Invoice'));

        $metaMock->expects($this->atLeastOnce())
            ->method('getFieldForColumn')
            ->with($this->equalTo('children'))
            ->will($this->returnValue('children'));

        $metaMock->expects($this->atLeastOnce())
            ->method('getTableName')
            ->will($this->returnValue('invoices'));

        $this->em->expects($this->any())
            ->method('getClassMetadata')
            ->with($this->equalTo('Invoice'))
            ->will($this->returnValue($metaMock));

        $conversion = new EntityCountConversion();
        $output = $conversion->convertSqlField('i.id', array('table_name' => null, 'table_field' => null), $hints);

        $this->assertEquals("(SELECT COUNT(*) FROM invoices WHERE children = i.id)", $output);
    }

    protected function getExtensions()
    {
        $dbalExtension = new DoctrineDbalExtension();
        $ormExtension = new DoctrineOrmExtension($this->emRegistry);

        return array($dbalExtension, $ormExtension);
    }

    protected function createRegistryMock($name, $em)
    {
        $registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $registry->expects($this->any())
            ->method('getManager')
            ->with($this->equalTo($name))
            ->will($this->returnValue($em));

        return $registry;
    }
}
