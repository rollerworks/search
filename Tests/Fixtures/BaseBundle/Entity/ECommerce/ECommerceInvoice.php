<?php

namespace Rollerworks\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce;

use \Rollerworks\RecordFilterBundle\Annotation as RecordFilter;

/**
 * ECommerce-Invoice
 *
 * @RecordFilter\Field("id", type="Number")
 * @RecordFilter\Field("label", type="Rollerworks\RecordFilterBundle\Tests\InvoiceType")
 */
class ECommerceInvoice
{
    private $id;

    public function __construct()
    {
    }
}