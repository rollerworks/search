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
