<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface SearchFactoryInterface
{
    /**
     * Create a new search field.
     *
     * @param string  $name
     * @param string  $type
     * @param array   $options
     * @param boolean $required
     *
     * @return FieldConfigInterface
     */
    public function createField($name, $type, array $options = array(), $required = false);

    /**
     * Create a new search field referenced by property.
     *
     * @param string  $class
     * @param string  $property
     * @param string  $name
     * @param string  $type
     * @param array   $options
     * @param boolean $required
     *
     * @return FieldConfigInterface
     */
    public function createFieldForProperty($class, $property, $name, $type, array $options = array(), $required = false);

    /**
     * Create a new FieldsetBuilderInterface instance.
     *
     * @param string $name
     *
     * @return FieldsetBuilder Interface
     */
    public function createFieldSetBuilder($name);
}
