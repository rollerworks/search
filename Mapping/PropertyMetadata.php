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
use Rollerworks\Bundle\RecordFilterBundle\Record\Sql\SqlValueConversionInterface;

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
    public $sqlConversion = array('service' => null, 'params' => array());

    /**
     * Set SQL conversion configuration.
     *
     * @param string $service
     * @param array  $params
     */
    public function setSqlConversion($service, array $params = array())
    {
        $this->sqlConversion = array('service' => $service, 'params' => $params);
    }

    /**
     * @return boolean
     */
    public function hasSqlConversion()
    {
        return null !== $this->sqlConversion['service'];
    }

    /**
     * @return string|null
     */
    public function getSqlConversionService()
    {
        return $this->sqlConversion['service'];
    }

    /**
     * @return array|null
     */
    public function getSqlConversionParams()
    {
        return $this->sqlConversion['params'];
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

            $this->sqlConversion,
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

            $this->sqlConversion,
            $this->widgetsConfig
        ) = unserialize($str);

        $this->reflection = new \ReflectionProperty($this->class, $this->name);
        $this->reflection->setAccessible(true);
    }
}
