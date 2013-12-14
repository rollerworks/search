<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search\Tests\Extension\Doctrine\Orm\Type;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Rollerworks\Component\Search\Extension\Doctrine\Dbal\DoctrineDbalExtension;
use Rollerworks\Component\Search\Extension\Doctrine\Orm\DoctrineOrmExtension;
use Rollerworks\Component\Search\Test\FieldTypeTestCase;

class EntityCountTypeTest extends FieldTypeTestCase
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

    public function testCreate()
    {
        $this->factory->createFieldForProperty('Invoice', 'children', 'children', 'doctrine_orm_entity_count');
    }

    protected function getTestedType()
    {
        return 'doctrine_orm_entity_count';
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
