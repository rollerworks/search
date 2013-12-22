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
