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

use Rollerworks\Bundle\RecordFilterBundle\Mapping\Loader\AnnotationDriver;
use Rollerworks\Bundle\RecordFilterBundle\Mapping\PropertyMetadata;
use Doctrine\Common\Annotations\AnnotationReader;

use Rollerworks\Bundle\RecordFilterBundle\Mapping\FilterTypeConfig;
use Rollerworks\Bundle\RecordFilterBundle\Tests\TestCase;

require_once 'Tests/Fixtures/BaseBundle/Entity/ECommerce/ECommerceCustomer.php';
require_once 'Tests/Fixtures/BaseBundle/Entity/ECommerce/ECommerceInvoice.php';

class AnnotationMappingTest extends TestCase
{
    /**
     * @var AnnotationDriver
     */
    protected $mappingDriver;

    public function testBasics()
    {
        $reflection = new \ReflectionClass('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceCustomer');
        $class = $this->mappingDriver->loadMetadataForClass($reflection);

        $this->assertTrue(isset($class->propertyMetadata['id']));

        $property = new PropertyMetadata($reflection->name, 'id');
        $property->filter_name    = 'customer_id';
        $property->required       = false;
        $property->type           = new FilterTypeConfig('customer_type', array());
        $property->acceptRanges   = false;
        $property->acceptCompares = false;
        $property->setSqlValueConversion('customer_conversion', array());

        $this->assertEquals($property, $class->propertyMetadata['id']);
    }

    public function testAccepts()
    {
        $reflection = new \ReflectionClass('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice');
        $class = $this->mappingDriver->loadMetadataForClass($reflection);

        $this->assertTrue(isset($class->propertyMetadata['id']));
        $this->assertTrue(isset($class->propertyMetadata['date']));

        $property = new PropertyMetadata($reflection->name, 'id');
        $property->filter_name    = 'invoice_id';
        $property->required       = false;
        $property->type           = new FilterTypeConfig('number', array());
        $property->acceptRanges   = true;
        $property->acceptCompares = false;

        $this->assertEquals($property, $class->propertyMetadata['id']);

        $property = new PropertyMetadata($reflection->name, 'date');
        $property->filter_name    = 'invoice_date';
        $property->required       = false;
        $property->type           = new FilterTypeConfig('date', array());
        $property->acceptRanges   = false;
        $property->acceptCompares = true;

        $this->assertEquals($property, $class->propertyMetadata['date']);
    }

    protected function setUp()
    {
        parent::setUp();

        $reader = new AnnotationReader();
        $reader->addGlobalIgnoredName('Id');
        $reader->addGlobalIgnoredName('Column');
        $reader->addGlobalIgnoredName('GeneratedValue');
        $reader->addGlobalIgnoredName('OneToOne');
        $reader->addGlobalIgnoredName('OneToMany');

        $this->mappingDriver = new AnnotationDriver($reader);
    }
}
