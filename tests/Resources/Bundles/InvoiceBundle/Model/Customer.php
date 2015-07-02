<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Bundle\SearchBundle\Tests\Resources\Bundles\InvoiceBundle\Model;

use Rollerworks\Component\Search\Metadata as Search;

class Customer
{
    /**
     * @Search\Field("customer_id", type="customer_type")
     */
    private $id;
}
