<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle;

/**
 * FilterTypeConfig.
 *
 * Holds the configuration for a FieldType.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class FilterTypeConfig
{
    private $name;
    private $params;

    /**
     * @param string $name
     * @param array  $params
     */
    public function __construct($name, array $params = array())
    {
        $this->name = $name;
        $this->params = $params;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }
}
