<?php

/**
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Extension\Core\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class Birthday extends Constraint
{
    public $ageMessage = 'This value is not a valid birthday or age.';
    public $dateMessage = 'This value is not a valid birthday.';
    public $allowAge;
}
