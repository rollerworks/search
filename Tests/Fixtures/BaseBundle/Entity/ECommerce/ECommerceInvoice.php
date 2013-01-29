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
     * @RecordFilter\Field("invoice_id", type="number", AcceptRanges=true)
     */
    private $id;

    /**
     * @Column(type="string", unique=true)
     * @RecordFilter\Field("invoice_label", type="invoice_type")
     */
    private $label;

    /**
     * @Column(name="pubdate", type="date")
     * @RecordFilter\Field("invoice_date", type="date", AcceptCompares=true)
     */
    private $date;

    /**
     * @ManyToOne(targetEntity="ECommerceCustomer")
     * @JoinColumn(name="customer", referencedColumnName="id")
     *
     * @RecordFilter\Field("invoice_customer", type="number")
     */
    private $customer;

    /**
     * @Column(type="integer")
     * @RecordFilter\Field("invoice_status", type="status_type")
     */
    private $status;

    /**
     * @OneToMany(targetEntity="ECommerceInvoiceRow", mappedBy="ECommerceInvoice", cascade={"persist"})
     * @var ECommerceInvoiceRow[]
     */
    private $rows;
}
