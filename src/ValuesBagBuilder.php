<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
     */
    public function __construct($parent)
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
