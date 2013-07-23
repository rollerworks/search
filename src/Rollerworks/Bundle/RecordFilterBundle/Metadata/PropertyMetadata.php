<?php

/*
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Metadata;

use Metadata\PropertyMetadata as BasePropertyMetadata;
use Rollerworks\Bundle\RecordFilterBundle\Metadata\Doctrine\OrmConfig;

/**
 * PropertyMetadata.
 */
class PropertyMetadata extends BasePropertyMetadata
{
    public $filter_name;
    public $label;
    public $required = false;

    public $acceptRanges = false;
    public $acceptCompares = false;

    /**
     * @var FilterTypeConfig
     */
    public $type = null;

    /**
     * @var array[]
     */
    public $doctrineConfig = array();

    /**
     * @param string $type
     *
     * @return OrmConfig|null
     */
    public function getDoctrineConfig($type)
    {
        if (!isset($this->doctrineConfig[$type])) {
            return null;
        }

        return $this->doctrineConfig[$type];
    }

    /**
     * @param string $type
     * @param object $value
     */
    public function setDoctrineConfig($type, $value)
    {
        $this->doctrineConfig[$type] = $value;
    }

    /**
     * @return string
     */
    public function serialize()
    {
        return serialize(array(
            $this->filter_name,
            $this->label,
            $this->type,

            $this->required,
            $this->acceptRanges,
            $this->acceptCompares,

            $this->doctrineConfig,
            parent::serialize(),
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
            $this->filter_name,
            $this->label,
            $this->type,

            $this->required,
            $this->acceptRanges,
            $this->acceptCompares,

            $this->doctrineConfig,
            $parentStr
        ) = unserialize($str);

        parent::unserialize($parentStr);
    }
}
