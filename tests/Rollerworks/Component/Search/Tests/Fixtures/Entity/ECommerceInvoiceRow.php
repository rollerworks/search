<?php

/**
 * This file is part of RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Doctrine\Tests\Fixtures\Entity;

/**
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
     */
    private $invoice_id;

    /**
     * @Column(type="string")
     */
    private $label;

    /**
     * @Column(name="price" type="decimal", precision=0, scale=2)
     */
    private $price;
}
