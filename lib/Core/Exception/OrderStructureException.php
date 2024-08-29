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

namespace Rollerworks\Component\Search\Exception;

final class OrderStructureException extends InputProcessorException
{
    public static function invalidValue(string $fieldName): self
    {
        return new self('', 'Field {{ field }} only accepts a single simple-value.', ['{{ field }}' => $fieldName]);
    }

    public static function noGrouping(): self
    {
        return new self('', 'Order clauses cannot be placed in a group.');
    }
}
