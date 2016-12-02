<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Exporter;

use Rollerworks\Component\Search\SearchCondition;

/**
 * Exports the SearchCondition as a JSON object.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class JsonExporter extends ArrayExporter
{
    /**
     * Exports the SearchCondition.
     *
     * @param SearchCondition $condition The SearchCondition to export
     *
     * @return string
     */
    public function exportCondition(SearchCondition $condition)
    {
        return json_encode(parent::exportCondition($condition));
    }
}
