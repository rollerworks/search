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

use Rollerworks\Component\Search\Exception\BadMethodCallException;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface FieldSetBuilderInterface
{
    /**
     * Add a field to the builder.
     *
     * @param string $name    Name of search field
     * @param string $type    The FQCN of the type
     * @param array  $options Array of options for building the field
     *
     * @return self
     */
    public function add(string $name, string $type, array $options = []);

    /**
     * Set a field on the builder.
     *
     * @param FieldConfigInterface $field
     *
     * @return FieldSetBuilderInterface
     */
    public function set(FieldConfigInterface $field);

    /**
     * Remove a field from the set-builder.
     *
     * @param string $name
     *
     * @throws BadMethodCallException When the FieldSet has been already turned into a FieldSet instance
     *
     * @return self
     */
    public function remove($name);

    /**
     * Returns whether the set-builder has a field with the name.
     *
     * @param string $name
     *
     * @return bool
     */
    public function has($name);

    /**
     * Get a previously registered field from the set.
     *
     * @param string $name
     *
     * @return FieldConfigInterface
     */
    public function get($name);

    /**
     * Create the FieldSet using the fields set on the builder.
     *
     * @return FieldSet
     */
    public function getFieldSet(string $name = null);
}
