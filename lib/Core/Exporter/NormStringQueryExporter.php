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

namespace Rollerworks\Component\Search\Exporter;

use Rollerworks\Component\Search\Field\FieldConfig;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\SearchCondition;

/**
 * Exports the SearchCondition as StringQuery string.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class NormStringQueryExporter extends StringExporter
{
    protected function modelToExported($value, FieldConfig $field): string
    {
        return $this->modelToNorm($value, $field);
    }

    protected function resolveLabels(FieldSet $fieldSet): array
    {
        $labels = [];

        foreach ($fieldSet->all() as $name => $field) {
            $labels[$name] = $name;
        }

        return $labels;
    }
}
