<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Type;

/**
 * ChainableTypeInterface.
 *
 * An filter-type can implement this interface to be used inside an TypeChain.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface ChainableTypeInterface
{
    /**
     * Returns whether this type accepts the input.
     *
     * Input checking must be done as strict as possible to prevent an false-positive.
     *
     * @param string $input
     *
     * @return boolean
     */
    public function acceptInput($input);
}
