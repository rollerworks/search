<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests\Extension\Validator\Fixtures;

use Symfony\Component\Validator\Constraints as Assert;

final class UserModel
{
    /**
     * @Assert\NotBlank()
     * @Assert\Range(min=6)
     */
    private $id;
}
