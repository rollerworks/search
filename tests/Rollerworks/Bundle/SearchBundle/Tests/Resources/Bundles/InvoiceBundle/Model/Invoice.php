<?php

/*
 * This file is part of the RollerworksSearchBundle package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\SearchBundle\Tests\Resources\Bundles\InvoiceBundle\Model;

use Rollerworks\Component\Search\Metadata as Search;

class Invoice
{
    /**
     * @Search\Field("invoice_id", type="integer")
     */
    private $id;

    /**
     * @Search\Field("invoice_label", type="text")
     */
    private $label;

    /**
     * @Search\Field("invoice_date", type="date")
     */
    private $date;

    /**
     * @Search\Field("invoice_customer", type="integer")
     */
    private $customer;

    /**
     * @Search\Field("invoice_status", type="choice", options = {"choices" = {1 = "open", 2 = "closed"}})
     */
    private $status;
}
