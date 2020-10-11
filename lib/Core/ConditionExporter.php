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

/**
 * ConditionExporter defines the interface for SearchCondition exporters.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface ConditionExporter
{
    /**
     * Exports the SearchCondition to a portable format.
     *
     * The returned format can be anything, as long as it's possible
     * to 're-import' the exported search condition with a compatible
     * input processor.
     */
    public function exportCondition(SearchCondition $condition);
}
