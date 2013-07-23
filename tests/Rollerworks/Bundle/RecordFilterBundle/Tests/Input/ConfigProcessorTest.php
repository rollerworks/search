<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Tests\Input;

use Rollerworks\Bundle\RecordFilterBundle\Metadata\Driver\AnnotationDriver;
use Rollerworks\Bundle\RecordFilterBundle\Input\ConfigProcessor;
use Rollerworks\Bundle\RecordFilterBundle\Input\FilterQuery;
use Rollerworks\Bundle\RecordFilterBundle\FilterField;
use Rollerworks\Bundle\RecordFilterBundle\FieldSet;
use Metadata\MetadataFactory;

use Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceProductSimple;
use Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceProductTwo;
use Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceProductReq;
use Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceProductWithType;
use Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceProductWithType2;
use Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceProductRange;
use Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceProductCompares;
use Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice;
use Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoiceWithParams;

class ConfigProcessorTest// extends \Rollerworks\Bundle\RecordFilterBundle\Tests\TestCase
{
    /**
     * @var ConfigProcessor
     */
    protected $configProcessor;

    protected function setUp()
    {
        $driver = new AnnotationDriver(new \Doctrine\Common\Annotations\AnnotationReader());
        $factory = new MetadataFactory($driver);

        $this->configProcessor = new ConfigProcessor($factory);
    }

    public function testOneField()
    {
        $entity = new ECommerceProductSimple();
        $input = new FilterQuery();

        $this->configProcessor->fillInputConfig($input->getFieldSet(), $entity);

        $set = new FieldSet();
        $set->set('product_id', new FilterField('product_id'));

        $this->assertEquals($set, $input->getFieldSet());
    }

    public function testTwoFields()
    {
        $entity = new ECommerceProductTwo();
        $input = new FilterQuery();

        $this->configProcessor->fillInputConfig($input->getFieldSet(), $entity);

        $set = new FieldSet();
        $set->set('product_id', new FilterField('product_id'));
        $set->set('product_name', new FilterField('product_name'));

        $this->assertEquals($set, $input->getFieldSet());
    }

    public function testWithRequired()
    {
        $entity = new ECommerceProductReq();
        $input = new FilterQuery();

        $this->configProcessor->fillInputConfig($input->getFieldSet(), $entity);

        $set = new FieldSet();
        $set->set('product_id', new FilterField('product_id', null, true));
        $set->set('product_name', new FilterField('product_name', null, false));

        $this->assertEquals($set, $input->getFieldSet());
    }

    public function testWithType()
    {
        $entity = new ECommerceProductWithType();
        $input = new FilterQuery();

        $this->configProcessor->fillInputConfig($input->getFieldSet(), $entity);

        $set = new FieldSet();
        $set->set('id', new FilterField('id', new \Rollerworks\Bundle\RecordFilterBundle\Type\Number(), true));
        $set->set('event_date', new FilterField('event_date', new \Rollerworks\Bundle\RecordFilterBundle\Type\DateTime()));

        $this->assertEquals($set, $input->getFieldSet());
    }

    public function testTypeWithParameter()
    {
        $entity = new ECommerceProductWithType2();
        $input = new FilterQuery();

        $this->configProcessor->fillInputConfig($input->getFieldSet(), $entity);

        $set = new FieldSet();
        $set->set('product_id', new FilterField('product_id', new \Rollerworks\Bundle\RecordFilterBundle\Type\Number(), true));
        $set->set('product_event_date', new FilterField('product_event_date', new \Rollerworks\Bundle\RecordFilterBundle\Type\DateTime(true)));

        $this->assertEquals($set, $input->getFieldSet());
    }

    public function testTypeWithDynamicParameter()
    {
        $container = $this->createContainer();
        $container->set('temp_service', new SomeClass());

        $entity = new ECommerceInvoiceWithParams();
        $input = new FilterQuery();

        $this->configProcessor->setContainer($container);
        $this->configProcessor->fillInputConfig($input->getFieldSet(), $entity);

        $set = new FieldSet();
        $set->set('invoice_id', new FilterField('invoice_id', new \Rollerworks\Bundle\RecordFilterBundle\Type\Number()));
        $set->set('invoice_label', new FilterField('invoice_label', new \Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\InvoiceType('bar%temp_service%:getSomething')));

        $this->assertEquals($set, $input->getFieldSet());
    }

    public function testTypeWithRange()
    {
        $entity = new ECommerceProductRange();
        $input = new FilterQuery();

        $this->configProcessor->fillInputConfig($input->getFieldSet(), $entity);

        $set = new FieldSet();
        $set->set('product_id', new FilterField('product_id', new \Rollerworks\Bundle\RecordFilterBundle\Type\Number(), true, true));
        $set->set('product_name', new FilterField('product_name'));

        $this->assertEquals($set, $input->getFieldSet());
    }

    public function testTypeWitCompares()
    {
        $entity = new ECommerceProductCompares();
        $input = new FilterQuery();

        $this->configProcessor->fillInputConfig($input->getFieldSet(), $entity);

        $set = new FieldSet();
        $set->set('product_id', new FilterField('product_id', new \Rollerworks\Bundle\RecordFilterBundle\Type\Number(), true, false, true));
        $set->set('product_name', new FilterField('product_name'));

        $this->assertEquals($set, $input->getFieldSet());
    }

    public function testFromMultipleEntities()
    {
        $input = new FilterQuery();

        $entity = new ECommerceProductTwo();
        $this->configProcessor->fillInputConfig($input->getFieldSet(), $entity);

        $entity = new ECommerceInvoice();
        $this->configProcessor->fillInputConfig($input->getFieldSet(), $entity);

        $set = new FieldSet();
        $set->set('product_id', new FilterField('product_id'));
        $set->set('product_name', new FilterField('product_name'));

        $set->set('invoice_id', new FilterField('invoice_id', new \Rollerworks\Bundle\RecordFilterBundle\Type\Number()));
        $set->set('invoice_label', new FilterField('invoice_label', new \Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\InvoiceType()));

        $this->assertEquals($set, $input->getFieldSet());
    }

    public function testSetOnConstruct()
    {
        $fields = new FieldSet();

        $entity = new ECommerceProductTwo();
        $this->configProcessor->fillInputConfig($fields, $entity);

        $input = new FilterQuery($fields);

        $set = new FieldSet();
        $set->set('product_id', new FilterField('product_id'));
        $set->set('product_name', new FilterField('product_name'));

        $this->assertEquals($set, $input->getFieldSet());
    }
}

/**
 * @ignore
 */
class SomeClass
{
    public function getSomething($parameters)
    {
        return 'bar' . $parameters['foo'];
    }
}
