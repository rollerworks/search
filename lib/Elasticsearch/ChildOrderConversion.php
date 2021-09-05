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

namespace Rollerworks\Component\Search\Elasticsearch;

/**
 * The ChildOrderConversion converts a has_child QueryScript
 * to an usable format, for example dates are not natively
 * supported for sorting, and need to be converted milliseconds.
 *
 * Caution: This must not be combined with other Elasticsearch conversions.
 */
interface ChildOrderConversion
{
    /**
     * Returns the query-script in transformed format.
     *
     * @param string $script either doc["field-name"].value
     *
     * @return string either doc["field-name"].value.millis
     */
    public function convert(string $property, string $script): string;
}
