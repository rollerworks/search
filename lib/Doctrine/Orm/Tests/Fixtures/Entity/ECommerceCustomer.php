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
#[ORM\Table(name: 'customers', options: ['collate' => 'utf8_bin'])]
class ECommerceCustomer
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    public $id;

    #[ORM\Column(name: 'first_name', type: 'string')]
    public $firstName;

    #[ORM\Column(name: 'last_name', type: 'string')]
    public $lastName;

    #[ORM\Column(type: 'date')]
    public $birthday;

    #[ORM\Column(type: 'datetime')]
    public $regdate;
}
