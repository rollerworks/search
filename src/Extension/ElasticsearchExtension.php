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

/**
 * Class ElasticsearchExtension.
 */
class ElasticsearchExtension extends AbstractExtension
{
    /**
     * {@inheritdoc}
     */
    protected function loadTypesExtensions(): array
    {
        return [
            new Type\FieldTypeExtension(),
            new Type\DateTypeExtension(new Conversion\DateConversion()),
        ];
    }
}
