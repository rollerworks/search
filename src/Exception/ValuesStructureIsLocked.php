<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
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
            'Unable to change the values of a locked data structure (ValuesGroup and ValuesBag).'
        );
    }
}
