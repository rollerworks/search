<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search\Extension\Core\Constraints;

use Symfony\Component\Validator\Constraint;

class Birthday extends Constraint
{
    public $ageMessage = 'This value is not a valid birthday or age.';

    public $dateMessage = 'This value is not a valid birthday.';

    public $allowAge;
}
