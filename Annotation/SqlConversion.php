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
 * SqlConversion Annotation class.
 *
 * @Annotation
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class SqlConversion
{
    /**
     * @var string
     */
    protected $service;

    /**
     * @var array
     */
    protected $params = array();

    /**
     * Constructor.
     *
     * @param array $data An array of key/value parameters
     *
     * @throws \BadMethodCallException
     * @throws \UnexpectedValueException
     */
    public function __construct(array $data)
    {
        $this->service = null;

        if (isset($data['value'])) {
            $this->service = $data['value'];
            unset($data['value']);
        }

        $this->params = $data;

        if (empty($this->service)) {
            throw new \UnexpectedValueException(sprintf("Property '%s' on annotation '%s' is required.", 'service', get_class($this)));
        }
    }

    /**
     * @param string $service
     *
     * @throws \UnexpectedValueException
     */
    public function setService($service)
    {
        if (empty($service)) {
            throw new \UnexpectedValueException(sprintf("Property '%s' on annotation '%s' is required.", 'service', get_class($this)));
        }

        $this->service = $service;
    }

    /**
     * @return string
     */
    public function getService()
    {
        return $this->service;
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
