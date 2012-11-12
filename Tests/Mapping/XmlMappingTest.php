<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Tests\Mapping;

use Rollerworks\Bundle\RecordFilterBundle\Metadata\Loader\XmlFileLoader;
use Rollerworks\Bundle\RecordFilterBundle\Metadata\PropertyMetadata;
use Rollerworks\Bundle\RecordFilterBundle\Metadata\FilterTypeConfig;
use Rollerworks\Bundle\RecordFilterBundle\Metadata\Doctrine\OrmConfig;
use Rollerworks\Bundle\RecordFilterBundle\Tests\TestCase;
use Metadata\Driver\FileLocator;

require_once 'Tests/Fixtures/UserBundle/Entity/User/BaseUser.php';
require_once 'Tests/Fixtures/UserBundle/Entity/User/UserAddress.php';
require_once 'Tests/Fixtures/UserBundle/Entity/User/User2.php';
require_once 'Tests/Fixtures/UserBundle/Entity/User/User3.php';
require_once 'Tests/Fixtures/UserBundle/Entity/User/User4.php';

class XmlMappingTest extends TestCase
{
    /**
     * @var XmlFileLoader
     */
    protected $loader;

    public function testBasics()
    {
        $reflection = new \ReflectionClass('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\UserBundle\Entity\User\BaseUser');
        $class = $this->loader->loadMetadataForClass($reflection);
        $this->assertTrue(isset($class->propertyMetadata['id']));

        $property = new PropertyMetadata($reflection->name, 'id');
        $property->filter_name    = 'customer_id';
        $property->required       = false;
        $property->type           = new FilterTypeConfig('customer_type');
        $property->acceptRanges   = false;
        $property->acceptCompares = false;

        $this->assertEquals($property, $class->propertyMetadata['id']);
        $this->assertEquals($property, unserialize(serialize($property)));
    }

    public function testBasicsAccepts()
    {
        $reflection = new \ReflectionClass('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\UserBundle\Entity\User\UserAddress');
        $class = $this->loader->loadMetadataForClass($reflection);
        $this->assertTrue(isset($class->propertyMetadata['name']));

        $property = new PropertyMetadata($reflection->name, 'name');
        $property->filter_name    = 'address_label';
        $property->label          = 'address_name';
        $property->required       = false;
        $property->type           = new FilterTypeConfig('text');
        $property->acceptRanges   = false;
        $property->acceptCompares = false;

        $this->assertEquals($property, $class->propertyMetadata['name']);
        $this->assertEquals($property, unserialize(serialize($property)));

        $property = new PropertyMetadata($reflection->name, 'id');
        $property->filter_name    = 'address_id';
        $property->required       = false;
        $property->type           = new FilterTypeConfig('number');
        $property->acceptRanges   = true;
        $property->acceptCompares = false;

        $this->assertEquals($property, $class->propertyMetadata['id']);
        $this->assertEquals($property, unserialize(serialize($property)));
    }

    public function testDoctrineOrm()
    {
        $reflection = new \ReflectionClass('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\UserBundle\Entity\User\User2');
        $class = $this->loader->loadMetadataForClass($reflection);
        $this->assertTrue(isset($class->propertyMetadata['id']));

        $property = new PropertyMetadata($reflection->name, 'id');
        $property->filter_name    = 'customer_id';
        $property->required       = false;
        $property->type           = new FilterTypeConfig('customer_type');
        $property->acceptRanges   = false;
        $property->acceptCompares = false;

        $ormConfig = new OrmConfig();
        $ormConfig->setValueConversion('customer_conversion');
        $property->setDoctrineConfig('orm', $ormConfig);

        $this->assertEquals($property, $class->propertyMetadata['id']);
        $this->assertEquals($property, unserialize(serialize($property)));
    }

    public function testDoctrineOrm2()
    {
        $reflection = new \ReflectionClass('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\UserBundle\Entity\User\User3');
        $class = $this->loader->loadMetadataForClass($reflection);
        $this->assertTrue(isset($class->propertyMetadata['id']));

        $property = new PropertyMetadata($reflection->name, 'id');
        $property->filter_name    = 'customer_id';
        $property->required       = false;
        $property->type           = new FilterTypeConfig('customer_type');
        $property->acceptRanges   = false;
        $property->acceptCompares = false;

        $ormConfig = new OrmConfig();
        $ormConfig->setFieldConversion('customer_conversion');
        $property->setDoctrineConfig('orm', $ormConfig);

        $this->assertEquals($property, $class->propertyMetadata['id']);
        $this->assertEquals($property, unserialize(serialize($property)));
    }

    public function testDoctrineOrm3()
    {
        $reflection = new \ReflectionClass('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\UserBundle\Entity\User\User4');
        $class = $this->loader->loadMetadataForClass($reflection);
        $this->assertTrue(isset($class->propertyMetadata['id']));

        $property = new PropertyMetadata($reflection->name, 'id');
        $property->filter_name    = 'customer_id';
        $property->required       = false;
        $property->type           = new FilterTypeConfig('customer_type');
        $property->acceptRanges   = false;
        $property->acceptCompares = false;

        $ormConfig = new OrmConfig();
        $ormConfig->setFieldConversion('customer_conversion');
        $ormConfig->setValueConversion('customer_conversion');
        $property->setDoctrineConfig('orm', $ormConfig);

        $this->assertEquals($property, $class->propertyMetadata['id']);
        $this->assertEquals($property, unserialize(serialize($property)));
    }

    public function testDoctrineOrmWithParams()
    {
        $reflection = new \ReflectionClass('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\UserBundle\Entity\User\User5');
        $class = $this->loader->loadMetadataForClass($reflection);
        $this->assertTrue(isset($class->propertyMetadata['id']));

        $property = new PropertyMetadata($reflection->name, 'id');
        $property->filter_name    = 'customer_id';
        $property->required       = false;
        $property->type           = new FilterTypeConfig('customer_type');
        $property->acceptRanges   = false;
        $property->acceptCompares = false;

        $ormConfig = new OrmConfig();
        $ormConfig->setFieldConversion('customer_conversion', array('foo' => 'bar'));
        $property->setDoctrineConfig('orm', $ormConfig);

        $this->assertEquals($property, $class->propertyMetadata['id']);
        $this->assertEquals($property, unserialize(serialize($property)));
    }

    public function testDoctrineOrmWithComplexParams()
    {
        $reflection = new \ReflectionClass('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\UserBundle\Entity\User\User6');
        $class = $this->loader->loadMetadataForClass($reflection);
        $this->assertTrue(isset($class->propertyMetadata['id']));

        $property = new PropertyMetadata($reflection->name, 'id');
        $property->filter_name    = 'customer_id';
        $property->required       = false;
        $property->type           = new FilterTypeConfig('customer_type');
        $property->acceptRanges   = false;
        $property->acceptCompares = false;

        $ormConfig = new OrmConfig();
        $ormConfig->setFieldConversion('customer_conversion', array(
            'foo' => array(
                'some' => 'he',
                'de' => array(156),
                'doctor' => array(array('who', 'zeus'))
            )
        ));

        $property->setDoctrineConfig('orm', $ormConfig);

        $this->assertEquals($property, $class->propertyMetadata['id']);
        $this->assertEquals($property, unserialize(serialize($property)));
    }

    protected function setUp()
    {
        parent::setUp();

        $locator = new FileLocator(array(
            'Rollerworks\\Bundle\\RecordFilterBundle\\Tests\\Fixtures\\UserBundle' => realpath(__DIR__ . '/../../Tests/Fixtures/UserBundle/Resources/config/record_filter'),
        ));

        $this->loader = new XmlFileLoader($locator);
    }
}
