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

namespace Rollerworks\Component\Search\Extension\Doctrine\Dbal;

use Rollerworks\Component\Search\AbstractExtension;
use Rollerworks\Component\Search\Extension\Doctrine\Dbal\Conversion\AgeDateConversion;
use Rollerworks\Component\Search\Extension\Doctrine\Dbal\Conversion\MoneyValueConversion;

class DoctrineDbalExtension extends AbstractExtension
{
    protected function loadTypesExtensions(): array
    {
        return [
            new Type\FieldTypeExtension(),
            new Type\DateTimeTypeExtension(),
            new Type\BirthdayTypeExtension(new AgeDateConversion()),
            new Type\MoneyTypeExtension(new MoneyValueConversion()),
        ];
    }

    protected function loadTypes(): array
    {
        return [
            new Type\ChildCountType(),
        ];
    }
}
