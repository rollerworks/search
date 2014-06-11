<?php

/**
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search;

/**
 * ExporterInterface defines the interface for SearchCondition exporters.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface ExporterInterface
{
    /**
     * Exports the SearchCondition to a portable format.
     *
     * @param SearchConditionInterface $condition
     *
     * @return mixed
     */
    public function exportCondition(SearchConditionInterface $condition);
}
