<?php

/*
 * This file is part of the RollerworksSearchBundle package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Bundle\SearchBundle\Tests\Resources\Bundles\InvoiceBundle\Model;

use Rollerworks\Component\Search\Metadata as Search;

class InvoiceRow
{
    private $id;
    private $invoice_id;

    /**
     * @Search\Field("invoice_label")
     */
    private $label;

    /**
     * @Search\Field("invoice_price", type="money")
     */
    private $price;
}
