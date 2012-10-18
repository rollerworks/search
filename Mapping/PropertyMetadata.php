<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Mapping;

use Metadata\PropertyMetadata as BasePropertyMetadata;

/**
 * PropertyMetadata.
 */
class PropertyMetadata extends BasePropertyMetadata
{
    public $filter_name;
    public $required;

    public $acceptRanges;
    public $acceptCompares;

    /**
     * @var FilterTypeConfig
     */
    public $type;

    public $widgetsConfig = array();
    public $sqlValueConversion = array('service' => null, 'params' => array());

    /**
     * Set SQL conversion configuration.
     *
     * @param string $service
     * @param array  $params
     */
    public function setSqlValueConversion($service, array $params = array())
    {
        $this->sqlValueConversion = array('service' => $service, 'params' => $params);
    }

    /**
     * @return boolean
     */
    public function hasSqlValueConversion()
    {
        return null !== $this->sqlValueConversion['service'];
    }

    /**
     * @return string|null
     */
    public function getSqlValueConversionService()
    {
        return $this->sqlValueConversion['service'];
    }

    /**
     * @return array
     */
    public function getSqlValueConversionParams()
    {
        return $this->sqlValueConversion['params'];
    }

    /**
     * @return string
     */
    public function serialize()
    {
        return serialize(array(
            $this->class,
            $this->name,
            $this->filter_name,
            $this->type,

            $this->required,
            $this->acceptRanges,
            $this->acceptCompares,

            $this->sqlValueConversion,
            $this->widgetsConfig
        ));
    }

    /**
     * @param string $str
     *
     * @return mixed
     */
    public function unserialize($str)
    {
        list(
            $this->class,
            $this->name,
            $this->filter_name,
            $this->type,

            $this->required,
            $this->acceptRanges,
            $this->acceptCompares,

            $this->sqlValueConversion,
            $this->widgetsConfig
        ) = unserialize($str);

        $this->reflection = new \ReflectionProperty($this->class, $this->name);
        $this->reflection->setAccessible(true);
    }
}
