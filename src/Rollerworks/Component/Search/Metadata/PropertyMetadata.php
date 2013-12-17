<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
     * @var boolean
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
