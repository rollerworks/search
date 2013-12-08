<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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

    protected function setUp()
    {
        $this->em = $this->getMock('Doctrine\ORM\EntityManagerInterface');
        $this->emRegistry = $this->createRegistryMock('default', $this->em);

        parent::setUp();
    }

    public function testConvertSqlField()
    {
        $field = $this->factory->createFieldForProperty('Invoice', 'children', 'children', 'doctrine_orm_entity_count');
        $hints = array(
            'searchField' => $field,
            'entityManager' => $this->em,
        );

        $conversion = new EntityCountConversion();
        $output = $conversion->convertSqlField('i.id', array('table_name' => 'invoices', 'table_field' => 'children'), $hints);

        $this->assertEquals("(SELECT COUNT(*) FROM invoices WHERE children = i.id)", $output);
    }

    public function testConvertSqlFieldWithAutoResolver()
    {
        $field = $this->factory->createFieldForProperty('Invoice', 'children', 'children', 'doctrine_orm_entity_count');
        $hints = array(
            'searchField' => $field,
            'entityManager' => $this->em,
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
