<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search;

use Rollerworks\Component\Search\Exception\InvalidArgumentException;

interface FieldSetRegistryInterface
{
    /**
     * Returns a FieldSetConfiguratorInterface by name.
     *
     * @param string $name The name of the FieldSet configurator
     *
     * @throws InvalidArgumentException if the configurator can not be retrieved
     *
     * @return FieldSetConfiguratorInterface
     */
    public function getConfigurator(string $name): FieldSetConfiguratorInterface;

    /**
     * Returns whether the given FieldSetConfigurator is supported.
     *
     * @param string $name The name of the FieldSet configurator
     *
     * @return bool
     */
    public function hasConfigurator(string $name): bool;
}
