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

use Psr\Container\ContainerInterface;

/**
 * Helps with lazily loading dependencies.
 *
 * This class is provided for easy of use, it should not be used
 * directly within your own code.
 *
 * @internal
 */
final class ClosureContainer implements ContainerInterface
{
    private $factories;
    private $values = [];

    /**
     * @param array|\Closure[] $factories
     */
    public function __construct(array $factories)
    {
        $this->factories = $factories;
    }

    public function has(string $id): bool
    {
        return isset($this->factories[$id]);
    }

    public function get(string $id): mixed
    {
        if (! isset($this->factories[$id])) {
            throw new ServiceNotFoundException($id);
        }

        if (true !== $factory = $this->factories[$id]) {
            $this->factories[$id] = true;
            $this->values[$id] = $factory();
        }

        return $this->values[$id];
    }
}
