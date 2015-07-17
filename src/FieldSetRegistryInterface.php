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
     * Returns whether the registry has the requested FieldSet.
     *
     * @param string $name
     *
     * @return bool
     */
    public function has($name);

    /**
     * Get a FieldSet from the registry.
     *
     * @param string $name
     *
     * @throws InvalidArgumentException when the requested FieldSet
     *                                  is not registered
     *
     * @return FieldSet
     */
    public function get($name);

    /**
     * Set a FieldSet on the registry.
     *
     * @param FieldSet $fieldSet
     *
     * @throws InvalidArgumentException when the FieldSet is
     *                                  already registered
     */
    public function add(FieldSet $fieldSet);

    /**
     * Returns all the available FieldSet's.
     *
     * @return FieldSet[] as FieldSet-name => {FieldSet object}
     */
    public function all();
}
