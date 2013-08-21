<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search\Extension\Validator\ViolationMapper;

use Rollerworks\Component\Search\ValuesGroup;
use Symfony\Component\Validator\ConstraintViolationInterface;

interface ViolationMapperInterface
{
    /**
     * Maps a constraint violation to a ValuesGroup in the ValuesGroup tree under
     * the given ValuesGroup.
     *
     * @param ConstraintViolationInterface $violation   The violations to map.
     * @param ValuesGroup                  $valuesGroup The root group of the tree to map it to.
     */
    public function mapViolation(ConstraintViolationInterface $violation, ValuesGroup $valuesGroup);
}
