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

namespace Rollerworks\Component\Search\Loader;

use Psr\Container\NotFoundExceptionInterface;
use Rollerworks\Component\Search\Exception\InvalidArgumentException;

final class ServiceNotFoundException extends InvalidArgumentException implements NotFoundExceptionInterface
{
    public function __construct(string $id)
    {
        parent::__construct(sprintf('You have requested a non-existent service "%s".', $id));
    }
}
