<?php

/*
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Tests\Doctrine\Dbal\Stub\Type\Doctrine\Conversion;

use Rollerworks\Component\Search\Doctrine\Dbal\ConversionHints;
use Rollerworks\Component\Search\Doctrine\Dbal\ValueConversionInterface;

final class InvoiceLabelConverter implements ValueConversionInterface
{
    public function requiresBaseConversion($input, array $options, ConversionHints $hints)
    {
        return false;
    }

    public function convertValue($input, array $options, ConversionHints $hints)
    {
        return (string) $input;
    }
}
