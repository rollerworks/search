<?php

/*
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Exception;

final class ValuesStructureIsLocked extends \BadMethodCallException implements ExceptionInterface
{
    public function __construct()
    {
        parent::__construct(
            'A Values-structure (ValuesGroup and ValuesBag) cannot be changed anymore once the data is locked.'
        );
    }
}
