<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
