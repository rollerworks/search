<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\RecordFilterBundle\Metadata;

use Metadata\PropertyMetadata as BasePropertyMetadata;

/**
 * PropertyMetadata
 */
class PropertyMetadata extends BasePropertyMetadata
{
    public $filter_name;

    public $required;

    public $type;

    public $acceptRanges;

    public $acceptCompares;

    public $params = array();

    public $widgetsConfig = array();

    /**
     * @return string
     */
    public function serialize()
    {
        return serialize(array(
            $this->class,
            $this->name,
            $this->filter_name,
            $this->required,
            $this->type,
            $this->acceptRanges,
            $this->acceptCompares,
            $this->params,
            $this->widgetsConfig,
        ));
    }

    /**
     * @param string $str
     */
    public function unserialize($str)
    {
        list($this->class, $this->name, $this->filter_name, $this->required, $this->type, $this->acceptRanges, $this->acceptCompares, $this->params, $this->widgetsConfig) = unserialize($str);

        $this->reflection = new \ReflectionProperty($this->class, $this->name);
        $this->reflection->setAccessible(true);
    }
}
