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

namespace Rollerworks\Component\Search;

use Rollerworks\Component\Search\Exception\BadMethodCallException;
use Rollerworks\Component\Search\Field\FieldConfig;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface FieldSetBuilder
{
    /**
     * Add a field to the builder.
     *
     * @param string $name    Name of search field
     * @param string $type    The FQCN of the type
     * @param array  $options Array of options for building the field
     *
     * @return static The builder
     */
    public function add(string $name, string $type, array $options = []);

    /**
     * Set a field on the builder.
     *
     * @param FieldConfig $field
     *
     * @return static The builder
     */
    public function set(FieldConfig $field);

    /**
     * Remove a field from the set-builder.
     *
     * @param string $name
     *
     * @throws BadMethodCallException When the FieldSet has been already turned into a FieldSet instance
     *
     * @return static The builder
     */
    public function remove(string $name);

    /**
     * Returns whether the set-builder has a field with the name.
     *
     * @param string $name
     *
     * @return bool
     */
    public function has(string $name): bool;

    /**
     * Get a previously registered field from the set.
     *
     * @param string $name
     *
     * @return FieldConfig
     */
    public function get(string $name): FieldConfig;

    /**
     * Create the FieldSet using the fields set on the builder.
     *
     * @param string $name
     *
     * @return FieldSet
     */
    public function getFieldSet(string $name = null): FieldSet;
}
