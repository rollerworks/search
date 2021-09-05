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

namespace Rollerworks\Component\Search\Elasticsearch\Extension;

use Rollerworks\Component\Search\AbstractExtension;

class ElasticsearchExtension extends AbstractExtension
{
    protected function loadTypesExtensions(): array
    {
        return [
            new Type\FieldTypeExtension(),
            new Type\OrderTypeExtension(),
            new Type\DateTypeExtension(new Conversion\DateConversion()),
            new Type\DateTimeTypeExtension(new Conversion\DateTimeConversion()),
            new Type\MoneyTypeExtension(new Conversion\CurrencyConversion()),
        ];
    }
}
