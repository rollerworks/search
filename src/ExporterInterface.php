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
