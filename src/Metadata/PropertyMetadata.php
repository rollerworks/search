<?php

/**
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Metadata;

use Metadata\PropertyMetadata as BasePropertyMetadata;

/**
 * PropertyMetadata.
 */
class PropertyMetadata extends BasePropertyMetadata
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
     * @return string
     */
    public function serialize()
    {
        return serialize(array(
            $this->fieldName,
            $this->type,
            $this->required,
            $this->options,

            parent::serialize(),
        ));
    }

    /**
     * @param string $str
     *
     * @return array
     */
    public function unserialize($str)
    {
        list(
            $this->fieldName,
            $this->type,
            $this->required,
            $this->options,
            $parentStr
        ) = unserialize($str);

        parent::unserialize($parentStr);
    }
}
