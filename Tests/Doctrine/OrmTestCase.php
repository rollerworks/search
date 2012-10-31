<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Tests\Doctrine;

use Rollerworks\Bundle\RecordFilterBundle\FilterField;
use Rollerworks\Bundle\RecordFilterBundle\FieldSet;
use Rollerworks\Bundle\RecordFilterBundle\Input\FilterQuery;
use Rollerworks\Bundle\RecordFilterBundle\Formatter\ModifierFormatter as Formatter;
use Rollerworks\Bundle\RecordFilterBundle\Formatter\Modifier\Validator;
use Rollerworks\Bundle\RecordFilterBundle\Type\Date;
use Rollerworks\Bundle\RecordFilterBundle\Type\Number;
use Rollerworks\Bundle\RecordFilterBundle\Type\Decimal;
use Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\InvoiceType;
use Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\StatusType;
use Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\CustomerType;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\AbstractQuery as OrmQuery;
use Doctrine\Tests\OrmTestCase as OrmTestCaseBase;

class OrmTestCase extends OrmTestCaseBase
{
    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @var Formatter
     */
    protected $formatter;

    /**
     * @var \Symfony\Component\Translation\TranslatorInterface
     */
    protected $translator;

    protected function setUp()
    {
        $this->em = $this->_getTestEntityManager();

        $this->em->getConfiguration()->addCustomStringFunction('RECORD_FILTER_FIELD_CONVERSION', 'Rollerworks\Bundle\RecordFilterBundle\Doctrine\Orm\Functions\FilterFieldConversion');
        $this->em->getConfiguration()->addCustomStringFunction('RECORD_FILTER_VALUE_CONVERSION', 'Rollerworks\Bundle\RecordFilterBundle\Doctrine\Orm\Functions\FilterValueConversion');

        $this->translator = $this->getMock('Symfony\\Component\\Translation\\TranslatorInterface');
        $this->translator->expects($this->any())
             ->method('trans')
             ->will($this->returnCallback(function ($id) { return $id; } ));

        $this->translator->expects($this->any())
             ->method('transChoice')
             ->will($this->returnCallback(function ($id) { return $id; } ));

        $this->formatter = new Formatter($this->translator);
        $this->formatter->registerModifier(new Validator());
    }

    /**
     * @return ContainerBuilder
     */
    protected function createContainer()
    {
        $container = new ContainerBuilder(new ParameterBag(array(
            'kernel.cache_dir' => __DIR__ . '/../.cache',
            'kernel.charset'   => 'UTF-8',
            'kernel.debug'     => false,
        )));

        $container->set('service_container', $container);

        return $container;
    }

    /**
     * @param null|string $fieldSetId
     *
     * @return FieldSet
     */
    public function getFieldSet($fieldSetId = null)
    {
        $fieldSet = new FieldSet('test');

        if ('invoice' == $fieldSetId) {
            $fieldSet = new FieldSet('invoice');
            $fieldSet
                ->set('invoice_label',    FilterField::create('invoice', new InvoiceType(), false)->setPropertyRef('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice', 'label'))
                ->set('invoice_date',     FilterField::create('date', new Date(), false, true, true)->setPropertyRef('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice', 'date'))
                ->set('invoice_customer', FilterField::create('customer', new Number(), false, true, true)->setPropertyRef('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice', 'customer'))
                ->set('invoice_status',   FilterField::create('status', new StatusType())->setPropertyRef('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice', 'status'))
                ->set('invoice_price',    FilterField::create('status', new Decimal(), false, true, true)->setPropertyRef('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoiceRow', 'price'))
            ;

        } elseif ('customer' == $fieldSetId) {
            $fieldSet = new FieldSet('customer');
            $fieldSet
                ->set('customer_id', FilterField::create('id', new CustomerType(), false, true, true)->setPropertyRef('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceCustomer', 'id'))
            ;
        } elseif ('user' == $fieldSetId) {
            $fieldSet = new FieldSet('customer');
            $fieldSet
                ->set('user_id', FilterField::create('id', new CustomerType(), false, true, true)->setPropertyRef('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceCustomer3', 'id'))
                ->set('birthday', FilterField::create('birthday', null, false, true, true)->setPropertyRef('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceCustomer3', 'birthday'))
            ;
        } elseif ('invoice_with_customer' == $fieldSetId) {
            $fieldSet = new FieldSet('invoice');
            $fieldSet
                ->set('invoice_label',    FilterField::create('invoice', new InvoiceType(), false)->setPropertyRef('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice', 'label'))
                ->set('invoice_date',     FilterField::create('date', new Date(), false, true, true)->setPropertyRef('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice', 'date'))
                ->set('invoice_customer', FilterField::create('customer', new Number(), false, true, true)->setPropertyRef('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice', 'customer'))
                ->set('invoice_status',   FilterField::create('status', new StatusType())->setPropertyRef('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice', 'status'))
                ->set('invoice_price',    FilterField::create('status', new Decimal(), false, true, true)->setPropertyRef('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoiceRow', 'price'))

                ->set('customer_id', FilterField::create('id', new CustomerType(), false, true, true)->setPropertyRef('Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceCustomer', 'id'))
            ;
        }

        return $fieldSet;
    }

    /**
     * @param string $filterQuery
     * @param string $fieldSet
     *
     * @return FilterQuery
     */
    protected function newInput($filterQuery, $fieldSet = 'invoice')
    {
        if (!$fieldSet instanceof FieldSet) {
            $fieldSet = $this->getFieldSet($fieldSet);
        }

        $input = new FilterQuery($this->translator);
        $input->setFieldSet($fieldSet);
        $input->setInput($filterQuery);

        return $input;
    }

    /**
     * Cleans whitespace from the input SQL for easy testing.
     *
     * @param string|null $input
     *
     * @return string|null
     */
    protected function cleanSql($input)
    {
        if (null === $input) {
            return null;
        }

        return str_replace(array("(\n", ")\n"), array('(', ')'), $input);
    }

    /**
     * @param array    $expected
     * @param OrmQuery $query
     */
    protected function assertQueryParamsEquals(array $expected, OrmQuery $query)
    {
        // Parameter handling changed in Doctrine ORM 2.3
        if (version_compare(\Doctrine\ORM\Version::VERSION, '2.3.0', '>=')) {
            foreach ($expected as $name => $value) {
                $paramVal = $query->getParameter($name);
                $this->assertInstanceOf('Doctrine\ORM\Query\Parameter', $paramVal);
                $this->assertEquals($query->getParameter($name)->getValue(), (is_object($value) ? $value : (string) $value));
            }
        } else {
            foreach ($expected as $name => $value) {
                $this->assertEquals($query->getParameter($name), (is_object($value) ? $value : (string) $value));
            }
        }
    }

    /**
     * @return AnnotationReader
     */
    protected function newAnnotationsReader()
    {
        $annotationReader = new AnnotationReader();
        $annotationReader->addGlobalIgnoredName('Id');
        $annotationReader->addGlobalIgnoredName('Column');
        $annotationReader->addGlobalIgnoredName('GeneratedValue');
        $annotationReader->addGlobalIgnoredName('OneToOne');
        $annotationReader->addGlobalIgnoredName('OneToMany');

        return $annotationReader;
    }

    /**
     * @param string $platform
     *
     * @return \Doctrine\DBAL\Connection
     */
    protected function getConnectionMock($platform)
    {
        $connectionMock = $this->getMock('Doctrine\DBAL\Connection', array(), array(), '', false);
        $connectionMock
                ->expects($this->any())
                ->method('getDatabasePlatform')
                ->will($this->returnValue($platform));

        return $connectionMock;
    }
}
