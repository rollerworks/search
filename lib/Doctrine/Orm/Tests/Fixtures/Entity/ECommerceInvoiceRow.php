<?php

declare(strict_types=1);

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

#[ORM\Entity]
#[ORM\Table(name: 'invoice_rows', options: ['collation' => 'utf8_bin'])]
class ECommerceInvoiceRow
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public $id;

    #[ORM\ManyToOne(targetEntity: 'ECommerceInvoice', inversedBy: 'rows')]
    #[ORM\JoinColumn(name: 'invoice', referencedColumnName: 'invoice_id')]
    public $invoice;

    #[ORM\Column(type: 'string')]
    public $label;

    #[ORM\Column(type: 'integer')]
    public $quantity;

    #[ORM\Column(name: 'price', type: 'decimal', precision: 10, scale: 2)]
    public $price;

    #[ORM\Column(name: 'total', type: 'decimal', precision: 10, scale: 2)]
    public $total;
}
