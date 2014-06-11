<?php

/**
 * This file is part of RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Exporter;

use Rollerworks\Component\Search\SearchConditionInterface;

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
     * @param SearchConditionInterface $condition     The SearchCondition to export
     * @param boolean                  $useFieldAlias Use the localized field-alias instead of the actual name (default false)
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    public function exportCondition(SearchConditionInterface $condition, $useFieldAlias = false)
    {
        if ($useFieldAlias && null === $this->labelResolver) {
            throw new \RuntimeException('Unable resolve field-name to alias because no labelResolver is configured.');
        }

        return json_encode($this->exportGroup($condition->getValuesGroup(), $condition->getFieldSet(), $useFieldAlias, true), JSON_FORCE_OBJECT);
    }
}
