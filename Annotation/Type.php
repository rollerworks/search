<?php

/**
 * This file is part of the RollerworksRecordFilterBundle package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Annotation;

/**
 * Annotation class for filter-type.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * @Annotation
 */
class Type
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $params = array();

    /**
     * Constructor.
     *
     * @param array $data An array of key/value parameters
     */
    public function __construct(array $data)
    {
        $this->name = null;

        if (isset($data['value'])) {
            $this->name = $data['value'];
            unset($data['value']);
        }

        foreach ($data as $key => $value) {
            $this->params[$key] = $value;
        }
    }

    /**
     * @param string $type
     */
    public function setName($type)
    {
        $this->name = $type;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return boolean
     */
    public function hasParams()
    {
        return count($this->params) > 0;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }
}
