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
