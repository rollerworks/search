<?php

/**
 * This file is part of the RollerworksSearchBundle package.
 *
 * (c) 2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Bundle\SearchBundle\Tests\Resources\Bundles\InvoiceBundle\Model;

use Rollerworks\Component\Search\Metadata as Search;

class Address
{
    /**
     * @Search\Field("address_id", type="integer")
     */
    private $id;

    /**
     * @Search\Field("address_label", type="text")
     */
    private $name;

    /**
     * @Search\Field("address_street", type="text")
     */
    private $street;
}
