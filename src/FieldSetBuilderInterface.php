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
use Rollerworks\Component\Search\Exception\UnexpectedTypeException;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface FieldSetBuilderInterface
{
    /**
     * Add a field to the set-builder.
     *
     * Note. The possibility to pass a FieldTypeInterface as $type is deprecated
     * since 1.0.0-beta5 and will be removed in 2.0
     *
     * Its possible to also pass in a FieldTypeInterface
     *
     * @param string|FieldConfigInterface $field         Name of search field or an actual search field
     *                                                   object
     * @param string                      $type          Field type-name
     * @param array                       $options       Array of options for building the field
     * @param bool                        $required      Is the field required in a ValuesGroup and must it
     *                                                   always have a value (default is false)
     * @param string                      $modelClass    Optional Model class-name reference
     * @param string                      $modelProperty Model property reference
     *
     * @throws BadMethodCallException  When the FieldSet has been already turned into a FieldSet instance
     * @throws UnexpectedTypeException
     *
     * @return self
     */
    public function add($field, $type = null, array $options = [], $required = false, $modelClass = null, $modelProperty = null);

    /**
     * Remove a field from the set-builder.
     *
     * @param string $name
     *
     * @throws BadMethodCallException When the FieldSet has been already turned into a FieldSet instance
     *
     * @return FieldSetBuilderInterface
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
    public function getFieldSet();
}
