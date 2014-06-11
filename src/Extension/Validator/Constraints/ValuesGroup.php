<?php

/**
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Extension\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class ValuesGroup extends Constraint
{
    /**
     * Violation code marking an invalid form.
     */
    const ERR_INVALID = 1;

    /**
     * {@inheritdoc}
     */
    public function getTargets()
    {
        return self::CLASS_CONSTRAINT;
    }
}
