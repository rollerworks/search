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

namespace Rollerworks\Component\Search\Elasticsearch\Extension\Conversion;

use Rollerworks\Component\Search\Elasticsearch\ValueConversion;
use Rollerworks\Component\Search\Extension\Core\Model\MoneyValue;

class CurrencyConversion implements ValueConversion
{
    /**
     * Returns the converted value as a valid Elasticsearch value.
     *
     * @param MoneyValue $value
     */
    public function convertValue($value): string
    {
        return $value->value->getAmount();
    }
}
