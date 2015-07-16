<?php

/*
 * This file is part of the RollerworksSearch package.
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
     * @var string
     */
    public $type;

    /**
     * @var array
     */
    public $options = [];

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
    public function __construct($fieldName, $class, $property, $required = false, $type = null, array $options = [])
    {
        $this->fieldName = $fieldName;
        $this->class = $class;
        $this->property = $property;
        $this->type = $type;
        $this->options = $options;
    }
}
