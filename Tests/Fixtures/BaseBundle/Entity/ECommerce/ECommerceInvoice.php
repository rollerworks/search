<?php

namespace Rollerworks\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce;

use Doctrine\ORM\Mapping as ORM;
use Rollerworks\RecordFilterBundle\Annotation as RecordFilter;

/**
 * ECommerce-Invoice
 *
 * @ORM\Entity
 * @ORM\Table(name="invoices")
 */
class ECommerceInvoice
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @RecordFilter\Field("invoice_id", type="Number")
     */
    private $id;

    /**
     * @ORM\Column(type="string", unique=true)
     * @RecordFilter\Field("invoice_label", type="Rollerworks\RecordFilterBundle\Tests\Fixtures\InvoiceType")
     */
    private $label;
}