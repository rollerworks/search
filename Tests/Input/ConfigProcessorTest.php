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

        $this->configProcessor->fillInputConfig($input, $entity);

        $this->assertEquals(array(
            'product_id' => new FilterConfig('product_id')
        ), $input->getFieldsConfig());
    }

    function testTwoFields()
    {
        $entity = new ECommerceProductTwo();
        $input = new FilterQuery();

        $this->configProcessor->fillInputConfig($input, $entity);

        $this->assertEquals(array(
            'product_id' => new FilterConfig('product_id'),
            'product_name' => new FilterConfig('product_name'),
        ), $input->getFieldsConfig());
    }

    function testWithRequired()
    {
        $entity = new ECommerceProductReq();
        $input = new FilterQuery();

        $this->configProcessor->fillInputConfig($input, $entity);

        $this->assertEquals(array(
            'product_id' => new FilterConfig('product_id', null, true),
            'product_name' => new FilterConfig('product_name', null, false),
        ), $input->getFieldsConfig());
    }

    function testWithType()
    {
        $entity = new ECommerceProductWithType();
        $input = new FilterQuery();

        $this->configProcessor->fillInputConfig($input, $entity);

        $this->assertEquals(array(
            'id' => new FilterConfig('id', new \Rollerworks\RecordFilterBundle\Type\Number(), true),
            'event_date' => new FilterConfig('event_date', new \Rollerworks\RecordFilterBundle\Type\DateTime()),
        ), $input->getFieldsConfig());
    }

    function testTypeWithParamater()
    {
        $entity = new ECommerceProductWithType2();
        $input = new FilterQuery();

        $this->configProcessor->fillInputConfig($input, $entity);

        $this->assertEquals(array(
            'product_id' => new FilterConfig('product_id', new \Rollerworks\RecordFilterBundle\Type\Number(), true),
            'product_event_date' => new FilterConfig('product_event_date', new \Rollerworks\RecordFilterBundle\Type\DateTime(true)),
        ), $input->getFieldsConfig());
    }

    function testTypeWithDynamicParamater()
    {
        $container = $this->createContainer();
        $container->set('temp_service', new SomeClass());

        $entity = new ECommerceInvoiceWithParams();
        $input = new FilterQuery();

        $this->configProcessor->setContainer($container);
        $this->configProcessor->fillInputConfig($input, $entity);

        $this->assertEquals(array(
            'invoice_id' => new FilterConfig('invoice_id', new \Rollerworks\RecordFilterBundle\Type\Number()),
            'invoice_label' => new FilterConfig('invoice_label', new \Rollerworks\RecordFilterBundle\Tests\Fixtures\InvoiceType('bar%temp_service%:getSomething')),
        ), $input->getFieldsConfig());
    }

    function testTypeWithRange()
    {
        $entity = new ECommerceProductRange();
        $input = new FilterQuery();

        $this->configProcessor->fillInputConfig($input, $entity);

        $this->assertEquals(array(
            'product_id' => new FilterConfig('product_id', new \Rollerworks\RecordFilterBundle\Type\Number(), true, true),
            'product_name' => new FilterConfig('product_name'),
        ), $input->getFieldsConfig());
    }

    function testTypeWitCompares()
    {
        $entity = new ECommerceProductCompares();
        $input = new FilterQuery();

        $this->configProcessor->fillInputConfig($input, $entity);

        $this->assertEquals(array(
            'product_id' => new FilterConfig('product_id', new \Rollerworks\RecordFilterBundle\Type\Number(), true, false, true),
            'product_name' => new FilterConfig('product_name'),
        ), $input->getFieldsConfig());
    }

    function testFromMultipleEntities()
    {
        $input = new FilterQuery();

        $entity = new ECommerceProductTwo();
        $this->configProcessor->fillInputConfig($input, $entity);

        $entity = new ECommerceInvoice();
        $this->configProcessor->fillInputConfig($input, $entity);

        $this->assertEquals(array(
            'product_id' => new FilterConfig('product_id'),
            'product_name' => new FilterConfig('product_name'),

            'invoice_id' => new FilterConfig('invoice_id', new \Rollerworks\RecordFilterBundle\Type\Number()),
            'invoice_label' => new FilterConfig('invoice_label', new \Rollerworks\RecordFilterBundle\Tests\Fixtures\InvoiceType()),
        ), $input->getFieldsConfig());
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
