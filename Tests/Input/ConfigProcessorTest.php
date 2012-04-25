<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\RecordFilterBundle\Tests\Input;

use Rollerworks\RecordFilterBundle\Metadata\Driver\AnnotationDriver;
use Rollerworks\RecordFilterBundle\Input\ConfigProcessor;
use Rollerworks\RecordFilterBundle\Input\FilterQuery;
use Rollerworks\RecordFilterBundle\FilterConfig;
use Rollerworks\RecordFilterBundle\FieldSet;
use Metadata\MetadataFactory;

use Rollerworks\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceProductSimple;
use Rollerworks\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceProductTwo;
use Rollerworks\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceProductReq;
use Rollerworks\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceProductWithType;
use Rollerworks\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceProductWithType2;
use Rollerworks\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceProductRange;
use Rollerworks\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceProductCompares;
use Rollerworks\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice;
use Rollerworks\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoiceWithParams;

class ConfigProcessorTest extends \Rollerworks\RecordFilterBundle\Tests\TestCase
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

    function testOneField()
    {
        $entity = new ECommerceProductSimple();
        $input = new FilterQuery();

        $this->configProcessor->fillInputConfig($input->getFieldsConfig(), $entity);

        $set = new FieldSet();
        $set->set('product_id', new FilterConfig('product_id'));

        $this->assertEquals($set, $input->getFieldsConfig());
    }

    function testTwoFields()
    {
        $entity = new ECommerceProductTwo();
        $input = new FilterQuery();

        $this->configProcessor->fillInputConfig($input->getFieldsConfig(), $entity);

        $set = new FieldSet();
        $set->set('product_id', new FilterConfig('product_id'));
        $set->set('product_name', new FilterConfig('product_name'));

        $this->assertEquals($set, $input->getFieldsConfig());
    }

    function testWithRequired()
    {
        $entity = new ECommerceProductReq();
        $input = new FilterQuery();

        $this->configProcessor->fillInputConfig($input->getFieldsConfig(), $entity);

        $set = new FieldSet();
        $set->set('product_id', new FilterConfig('product_id', null, true));
        $set->set('product_name', new FilterConfig('product_name', null, false));

        $this->assertEquals($set, $input->getFieldsConfig());
    }

    function testWithType()
    {
        $entity = new ECommerceProductWithType();
        $input = new FilterQuery();

        $this->configProcessor->fillInputConfig($input->getFieldsConfig(), $entity);

        $set = new FieldSet();
        $set->set('id', new FilterConfig('id', new \Rollerworks\RecordFilterBundle\Type\Number(), true));
        $set->set('event_date', new FilterConfig('event_date', new \Rollerworks\RecordFilterBundle\Type\DateTime()));

        $this->assertEquals($set, $input->getFieldsConfig());
    }

    function testTypeWithParameter()
    {
        $entity = new ECommerceProductWithType2();
        $input = new FilterQuery();

        $this->configProcessor->fillInputConfig($input->getFieldsConfig(), $entity);

        $set = new FieldSet();
        $set->set('product_id', new FilterConfig('product_id', new \Rollerworks\RecordFilterBundle\Type\Number(), true));
        $set->set('product_event_date', new FilterConfig('product_event_date', new \Rollerworks\RecordFilterBundle\Type\DateTime(true)));

        $this->assertEquals($set, $input->getFieldsConfig());
    }

    function testTypeWithDynamicParameter()
    {
        $container = $this->createContainer();
        $container->set('temp_service', new SomeClass());

        $entity = new ECommerceInvoiceWithParams();
        $input = new FilterQuery();

        $this->configProcessor->setContainer($container);
        $this->configProcessor->fillInputConfig($input->getFieldsConfig(), $entity);

        $set = new FieldSet();
        $set->set('invoice_id', new FilterConfig('invoice_id', new \Rollerworks\RecordFilterBundle\Type\Number()));
        $set->set('invoice_label', new FilterConfig('invoice_label', new \Rollerworks\RecordFilterBundle\Tests\Fixtures\InvoiceType('bar%temp_service%:getSomething')));

        $this->assertEquals($set, $input->getFieldsConfig());
    }

    function testTypeWithRange()
    {
        $entity = new ECommerceProductRange();
        $input = new FilterQuery();

        $this->configProcessor->fillInputConfig($input->getFieldsConfig(), $entity);

        $set = new FieldSet();
        $set->set('product_id', new FilterConfig('product_id', new \Rollerworks\RecordFilterBundle\Type\Number(), true, true));
        $set->set('product_name', new FilterConfig('product_name'));

        $this->assertEquals($set, $input->getFieldsConfig());
    }

    function testTypeWitCompares()
    {
        $entity = new ECommerceProductCompares();
        $input = new FilterQuery();

        $this->configProcessor->fillInputConfig($input->getFieldsConfig(), $entity);

        $set = new FieldSet();
        $set->set('product_id', new FilterConfig('product_id', new \Rollerworks\RecordFilterBundle\Type\Number(), true, false, true));
        $set->set('product_name', new FilterConfig('product_name'));

        $this->assertEquals($set, $input->getFieldsConfig());
    }

    function testFromMultipleEntities()
    {
        $input = new FilterQuery();

        $entity = new ECommerceProductTwo();
        $this->configProcessor->fillInputConfig($input->getFieldsConfig(), $entity);

        $entity = new ECommerceInvoice();
        $this->configProcessor->fillInputConfig($input->getFieldsConfig(), $entity);

        $set = new FieldSet();
        $set->set('product_id', new FilterConfig('product_id'));
        $set->set('product_name', new FilterConfig('product_name'));

        $set->set('invoice_id', new FilterConfig('invoice_id', new \Rollerworks\RecordFilterBundle\Type\Number()));
        $set->set('invoice_label', new FilterConfig('invoice_label', new \Rollerworks\RecordFilterBundle\Tests\Fixtures\InvoiceType()));

        $this->assertEquals($set, $input->getFieldsConfig());
    }

    function testSetOnConstruct()
    {
        $fields = new FieldSet();

        $entity = new ECommerceProductTwo();
        $this->configProcessor->fillInputConfig($fields, $entity);

        $input = new FilterQuery($fields);

        $set = new FieldSet();
        $set->set('product_id', new FilterConfig('product_id'));
        $set->set('product_name', new FilterConfig('product_name'));

        $this->assertEquals($set, $input->getFieldsConfig());
    }
}

/**
 * @ignore
 */
class SomeClass
{
    function getSomething($parameters)
    {
        return 'bar' . $parameters['foo'];
    }
}
