<?php

declare(strict_types=1);

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search;

use Rollerworks\Component\Search\Value\ValuesGroup;

/**
 * @author Dalibor Karlović <dalibor@flexolabs.io>
 */
final class SearchOrder
{
    private $values;

    public function __construct(ValuesGroup $valuesGroup)
    {
        $this->values = $valuesGroup;
    }

    public function getValuesGroup(): ValuesGroup
    {
        return $this->values;
    }
}
