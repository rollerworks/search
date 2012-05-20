<?php

namespace Rollerworks\RecordFilterBundle\Tests\Fixtures\BaseBundle\Entity\ECommerce;

use Rollerworks\RecordFilterBundle\Annotation as RecordFilter;

/**
 * ECommerce-Invoice
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
     * @RecordFilter\Field("invoice_id", type="Number")
     */
    private $id;

    /**
     * @Column(type="string", unique=true)
     * @RecordFilter\Field("invoice_label", type="Rollerworks\RecordFilterBundle\Tests\Fixtures\InvoiceType")
     */
    private $label;

    /**
     * @Column(name="pubdate", type="date")
     * @RecordFilter\Field("invoice_date", type="Date")
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
     * @RecordFilter\Field("invoice_status", type="Rollerworks\RecordFilterBundle\Tests\Fixtures\StatusType")
     */
    private $status;

    /**
     * @OneToMany(targetEntity="ECommerceInvoiceRow", mappedBy="ECommerceInvoice", cascade={"persist"})
     * @var ECommerceInvoiceRow[]
     */
    private $rows;
}
