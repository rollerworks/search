<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search;

/**
 * Helper class for the SearchConditionBuilder.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class ValuesBagBuilder extends ValuesBag
{
    /**
     * @var SearchConditionBuilder
     */
    protected $parent;

    /**
     * Constructor.
     *
     * @param SearchConditionBuilder $parent
     */
    public function __construct(SearchConditionBuilder $parent)
    {
        $this->parent = $parent;
    }

    /**
     * @return SearchConditionBuilder
     */
    public function end()
    {
        return $this->parent;
    }
}
