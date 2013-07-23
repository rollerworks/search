<?php

/*
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Metadata\Doctrine;

/**
 * Doctrine OrmConfig class.
 */
class OrmConfig implements \Serializable
{
    /**
     * @var array
     */
    public $valueConversion = array('service' => null, 'params' => array());

    /**
     * @var array
     */
    public $fieldConversion = array('service' => null, 'params' => array());

    /**
     * Sets SQL conversion configuration.
     *
     * @param string $service
     * @param array  $params
     */
    public function setValueConversion($service, array $params = array())
    {
        $this->valueConversion = array('service' => $service, 'params' => $params);
    }

    /**
     * @return boolean
     */
    public function hasValueConversion()
    {
        return null !== $this->valueConversion['service'];
    }

    /**
     * @return string|null
     */
    public function getValueConversionService()
    {
        return $this->valueConversion['service'];
    }

    /**
     * @return array
     */
    public function getValueConversionParams()
    {
        return $this->valueConversion['params'];
    }

    /**
     * @return boolean
     */
    public function hasFieldConversion()
    {
        return null !== $this->fieldConversion['service'];
    }

    /**
     * Sets SQL conversion configuration.
     *
     * @param string $service
     * @param array  $params
     */
    public function setFieldConversion($service, array $params = array())
    {
        $this->fieldConversion = array('service' => $service, 'params' => $params);
    }

    /**
     * @return string|null
     */
    public function getFieldConversionService()
    {
        return $this->fieldConversion['service'];
    }

    /**
     * @return array
     */
    public function getFieldConversionParams()
    {
        return $this->fieldConversion['params'];
    }

    /**
     * @return string
     */
    public function serialize()
    {
        return serialize(array(
            $this->fieldConversion,
            $this->valueConversion,
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
            $this->fieldConversion,
            $this->valueConversion,
        ) = unserialize($str);
    }
}
