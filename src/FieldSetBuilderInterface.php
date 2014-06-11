<?php

/**
 * This file is part of RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
*/
interface FieldSetBuilderInterface
{
    /**
     * @param string|FieldConfigInterface $field
     * @param string|FieldTypeInterface   $type
     * @param array                       $options
     * @param boolean                     $required
     * @param string                      $modelClass
     * @param string                      $property
     *
     * @return FieldSetBuilderInterface
     */
    public function add($field, $type = null, array $options = array(), $required = false, $modelClass = null, $property = null);

    /**
     * @param string $name
     *
     * @return FieldSetBuilderInterface
     */
    public function remove($name);

    /**
     * @param string $name
     *
     * @return boolean
     */
    public function has($name);

    /**
     * @param string $name
     *
     * @return FieldConfigInterface
     */
    public function get($name);

    /**
     * @return FieldSet
     */
    public function getFieldSet();
}
