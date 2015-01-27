<?php

/*
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Metadata;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class SearchField
{
    /**
     * @var string
     */
    public $fieldName;

    /**
     * @var bool
     */
    public $required = false;

    /**
     * @var string
     */
    public $type = null;

    /**
     * @var array
     */
    public $options = array();

    /**
     * @var string
     */
    public $class;

    /**
     * @var string
     */
    public $property;

    /**
     * @param string      $fieldName
     * @param string      $class
     * @param string      $property
     * @param bool        $required
     * @param string|null $type
     * @param array       $options
     */
    public function __construct($fieldName, $class, $property, $required = false, $type = null, $options = array())
    {
        $this->fieldName = $fieldName;
        $this->class = $class;
        $this->property = $property;
        $this->required = $required;
        $this->type = $type;
        $this->options = $options;
    }
}
