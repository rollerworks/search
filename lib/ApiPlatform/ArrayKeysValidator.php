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

namespace Rollerworks\Component\Search\ApiPlatform;

use ApiPlatform\Exception\RuntimeException;

final class ArrayKeysValidator
{
    public static function assertKeysExists(array $input, array $required, string $path): void
    {
        if ([] !== $missing = array_diff_key(array_flip($required), $input)) {
            throw new RuntimeException(
                sprintf(
                    'Config "%s" is missing "%s", got "%s".',
                    $path,
                    implode('", "', array_flip($missing)),
                    implode('", "', array_keys($input))
                )
            );
        }
    }

    public static function assertOnlyKeys(array $input, array $accepted, string $path): void
    {
        if (array_diff(array_keys($input), $accepted) !== []) {
            throw new RuntimeException(
                sprintf(
                    'Config "%s" accepts only "%s", got "%s".',
                    $path,
                    implode('", "', $accepted),
                    implode('", "', array_keys($input))
                )
            );
        }
    }
}
