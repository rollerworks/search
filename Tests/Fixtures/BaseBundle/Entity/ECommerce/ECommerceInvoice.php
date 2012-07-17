<?php

namespace Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce;

use Rollerworks\Bundle\RecordFilterBundle\Annotation as RecordFilter;

/**
 * ECommerce-Invoice.
 *
 * @Entity
 * @Table(name="invoices")
 */
class ECommerceInvoice
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     * @RecordFilter\Field("invoice_id", type="Number", AcceptRanges=true)
     */
    private $id;

    /**
     * @Column(type="string", unique=true)
     * @RecordFilter\Field("invoice_label", type="Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\InvoiceType")
     */
    private $label;

    /**
     * @Column(name="pubdate", type="date")
     * @RecordFilter\Field("invoice_date", type="Date", AcceptCompares=true)
     */
    private $date;

    /**
     * @Column(type="integer")
     * @OneToOne(targetEntity="ECommerceInvoice", inversedBy="ECommerceInvoiceRow")
     *
     * @RecordFilter\Field("invoice_customer", type="Number")
     */
    private $customer;

    /**
     * @Column(type="integer")
     * @RecordFilter\Field("invoice_status", type="Rollerworks\Bundle\RecordFilterBundle\Tests\Fixtures\StatusType")
     */
    private $status;

    /**
     * @OneToMany(targetEntity="ECommerceInvoiceRow", mappedBy="ECommerceInvoice", cascade={"persist"})
     * @var ECommerceInvoiceRow[]
     */
    private $rows;
}
