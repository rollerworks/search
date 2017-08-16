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

use Rollerworks\Component\Search\Value\ValuesBag;

/**
 * Helper class for the SearchConditionBuilder.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class ValuesBagBuilder extends ValuesBag
{
    private $parent;

    /**
     * Constructor.
     *
     * @param SearchConditionBuilder $parent
     */
    public function __construct(SearchConditionBuilder $parent)
    {
        $this->parent = $parent;
    }

    public function end(): SearchConditionBuilder
    {
        return $this->parent;
    }

    public function toValuesBag(): ValuesBag
    {
        $valuesBag = new ValuesBag();
        $valuesBag->unserialize($this->serialize());

        return $valuesBag;
    }
}
