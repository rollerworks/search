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
     * @ORM\ManyToOne(targetEntity="ECommerceInvoice", inversedBy="rows")
     * @ORM\JoinColumn(name="invoice", referencedColumnName="invoice_id")
     */
    private $invoice;

    /**
     * @ORM\Column(type="string")
     */
    private $label;

    /**
     * @ORM\Column(name="price", type="decimal", precision=0, scale=2)
     */
    private $price;
}
