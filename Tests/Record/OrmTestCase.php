<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\RecordFilterBundle\Tests\Record;

use Rollerworks\RecordFilterBundle\FilterConfig;
use Rollerworks\RecordFilterBundle\FieldSet;
use Rollerworks\RecordFilterBundle\Input\FilterQuery;

use Rollerworks\RecordFilterBundle\Formatter\Formatter;
use Rollerworks\RecordFilterBundle\Formatter\Modifier\Validator;

use Rollerworks\RecordFilterBundle\Type\Date;
use Rollerworks\RecordFilterBundle\Type\Number;
use Rollerworks\RecordFilterBundle\Type\Decimal;

use Rollerworks\RecordFilterBundle\Tests\Fixtures\InvoiceType;
use Rollerworks\RecordFilterBundle\Tests\Fixtures\StatusType;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Doctrine\Tests\OrmTestCase as OrmTestCaseBase;

/**
 * Test the Validation generator. Its work is generating on-the-fly subclasses of a given model.
 * As you may have guessed, this is based on the Doctrine\ORM\Proxy module.
 */
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

    protected function setUp()
    {
        $this->em = $this->_getTestEntityManager();

        $translator = $this->getMock('Symfony\\Component\\Translation\\TranslatorInterface');
        $translator->expects($this->any())
             ->method('trans')
             ->will($this->returnCallback(function ($id) { return $id; } ));

        $translator->expects($this->any())
             ->method('transChoice')
             ->will($this->returnCallback(function ($id) { return $id; } ));

        $this->formatter = new Formatter($translator);
        $this->formatter->registerModifier(new Validator());
    }

    /**
     * @return \Symfony\Component\DependencyInjection\ContainerBuilder
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
     * @param string $filterQuery
     * @param string $fieldSetId
     * @return FilterQuery
     */
    protected function newInput($filterQuery, $fieldSetId = 'invoice')
    {
        $fieldSet = new FieldSet('test');

        if ('invoice' == $fieldSetId) {
            $fieldSet = new FieldSet('invoice');
            $fieldSet->set('invoice_label', new FilterConfig('invoice', new InvoiceType(), false, true, true))
                ->get('invoice_label')->setEntity('Rollerworks\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice', 'label');

            $fieldSet->set('invoice_date', new FilterConfig('date', new Date(), false, true, true))
                ->get('invoice_date')->setEntity('Rollerworks\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice', 'pubdate');

            $fieldSet->set('invoice_customer', new FilterConfig('customer', new Number(), false, true, true))
                ->get('invoice_customer')->setEntity('Rollerworks\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice', 'customer');

            $fieldSet->set('invoice_status', new FilterConfig('status', new StatusType()))
                ->get('invoice_status')->setEntity('Rollerworks\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoice', 'status');

            $fieldSet->set('invoice_price', new FilterConfig('status', new Decimal(), false, true, true))
                ->get('invoice_price')->setEntity('Rollerworks\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce\ECommerceInvoiceRow', 'price');
        }

        return new FilterQuery($fieldSet, $filterQuery);
    }

    protected function cleanSql($input)
    {
        return str_replace(array("(\n", ")\n"), array('(', ')'), $input);
    }
}
