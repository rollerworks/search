<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests\Doctrine\Orm\Fixtures\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="invoices")
 */
class ECommerceInvoice
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer", name="invoice_id")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", unique=true, nullable=true)
     */
    private $label;

    /**
     * @ORM\Column(name="pubdate", type="date", nullable=true)
     */
    private $date;

    /**
     * @ORM\ManyToOne(targetEntity="ECommerceCustomer")
     * @ORM\JoinColumn(name="customer", referencedColumnName="id")
     */
    private $customer;

    /**
     * @ORM\Column(type="integer")
     */
    private $status;

    /**
     * @ORM\OneToMany(targetEntity="ECommerceInvoiceRow", mappedBy="invoice", cascade={"persist"})
     */
    private $rows;

    /**
     * @ORM\ManyToOne(targetEntity="ECommerceInvoice", inversedBy="children")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="invoice_id")
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity="ECommerceInvoice", mappedBy="parent")
     */
    private $children;
}
