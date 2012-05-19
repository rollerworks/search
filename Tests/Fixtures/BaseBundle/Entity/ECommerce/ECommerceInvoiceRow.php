<?php

namespace Rollerworks\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce;

use Doctrine\ORM\Mapping as ORM;
use Rollerworks\RecordFilterBundle\Annotation as RecordFilter;

/**
 * ECommerce-Invoice row
 *
 * @ORM\Entity
 * @ORM\Table(name="invoice_rows")
 */
class ECommerceInvoiceRow
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @OneToOne(targetEntity="ECommerceInvoice", inversedBy="ECommerceInvoiceRow")
     * @var ECommerceInvoice
     */
    private $invoice_id;

    /**
     * @ORM\Column(type="string")
     * @RecordFilter\Field("invoice_label")
     */
    private $label;

    /**
     * @ORM\Column(name="price" type="decimal", precision=0, scale=2)
     * @RecordFilter\Field("invoice_date", type="Decimal")
     */
    private $price;
}
