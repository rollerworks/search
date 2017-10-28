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

use Rollerworks\Component\Search\ConditionExporter;
use Rollerworks\Component\Search\SearchCondition;

/**
 * Exports the SearchCondition as a JSON object.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class JsonExporter implements ConditionExporter
{
    private $exporter;

    /**
     * Constructor.
     *
     * @param ArrayExporter $arrayExporter
     */
    public function __construct(ArrayExporter $arrayExporter = null)
    {
        $this->exporter = $arrayExporter ?? new ArrayExporter();
    }

    /**
     * Exports the SearchCondition.
     *
     * @param SearchCondition $condition The SearchCondition to export
     *
     * @return string
     */
    public function exportCondition(SearchCondition $condition): string
    {
        return json_encode($this->exporter->exportCondition($condition));
    }
}
