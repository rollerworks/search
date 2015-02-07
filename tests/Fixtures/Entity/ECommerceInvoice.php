<?php

/*
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests\Fixtures\Entity;

/**
 * @Entity
 * @Table(name="invoices")
 */
class ECommerceInvoice
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @Column(type="string", unique=true)
     */
    private $label;

    /**
     * @Column(name="pubdate", type="date")
     */
    private $date;

    /**
     * @ManyToOne(targetEntity="ECommerceCustomer")
     * @JoinColumn(name="customer", referencedColumnName="id")
     */
    private $customer;

    /**
     * @Column(type="integer")
     */
    private $status;

    /**
     * @OneToMany(targetEntity="ECommerceInvoiceRow", mappedBy="ECommerceInvoice", cascade={"persist"})
     */
    private $rows;
}
