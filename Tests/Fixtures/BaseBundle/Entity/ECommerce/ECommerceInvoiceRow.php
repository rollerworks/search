<?php

namespace Rollerworks\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce;

use Rollerworks\RecordFilterBundle\Annotation as RecordFilter;

/**
 * ECommerce-Invoice row
 *
 * @Entity
 * @Table(name="invoice_rows")
 */
class ECommerceInvoiceRow
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @OneToOne(targetEntity="ECommerceInvoice", inversedBy="ECommerceInvoiceRow")
     * @var ECommerceInvoice
     */
    private $invoice_id;

    /**
     * @Column(type="string")
     * @RecordFilter\Field("invoice_label")
     */
    private $label;

    /**
     * @Column(name="price" type="decimal", precision=0, scale=2)
     * @RecordFilter\Field("invoice_date", type="Decimal")
     */
    private $price;
}
