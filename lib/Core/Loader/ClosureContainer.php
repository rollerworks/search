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
 * ClosureContainer helps with lazily loading dependencies.
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
     * @param \Closure[]|iterable $factories
     */
    public function __construct(iterable $factories)
    {
        $this->factories = $factories;
    }

    /**
     * {@inheritdoc}
     */
    public function has($id): bool
    {
        return isset($this->factories[$id]);
    }

    /**
     * {@inheritdoc}
     */
    public function get($id)
    {
        if (!isset($this->factories[$id])) {
            throw new ServiceNotFoundException($id);
        }

        if (true !== $factory = $this->factories[$id]) {
            $this->factories[$id] = true;
            $this->values[$id] = $factory();
        }

        return $this->values[$id];
    }
}
