<?php

/**
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Extension\Validator\ViolationMapper;

use Rollerworks\Component\Search\ValuesGroup;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface ViolationMapperInterface
{
    /**
     * Maps a constraint violation to a ValuesGroup in the ValuesGroup tree under
     * the given ValuesGroup.
     *
     * @param ConstraintViolationInterface $violation   The violations to map.
     * @param ValuesGroup                  $valuesGroup The root group of the tree to map it to.
     *
     * @return void
     */
    public function mapViolation(ConstraintViolationInterface $violation, ValuesGroup $valuesGroup);
}
