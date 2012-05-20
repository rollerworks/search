<?php

/**
 * This file is part of the RollerworksRecordFilterBundle package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\RecordFilterBundle\Annotation;

/**
 * SqlConversion Annotation class.
 *
 * @Annotation
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class SqlConversion
{
    protected $class;
    protected $params = array();

    /**
     * Constructor.
     *
     * @param array $data An array of key/value parameters.
     *
     * @throws \BadMethodCallException
     * @throws \UnexpectedValueException
     */
    public function __construct(array $data)
    {
        $this->class = null;

        if (isset($data['value'])) {
            $data['class'] = $data['value'];
            unset($data['value']);
        }

        foreach ($data as $key => $value) {
            if ('_' === $key[0]) {
                $this->params[substr($key, 1)] = $value;
                continue;
            }

            $method = 'set' . ucfirst($key);

            if (!method_exists($this, $method)) {
                throw new \BadMethodCallException(sprintf("Unknown property '%s' on annotation '%s'.", $key, get_class($this)));
            }

            $this->$method($value);
        }

        if (empty($this->class)) {
            throw new \UnexpectedValueException(sprintf("Property '%s' on annotation '%s' is required.", 'class', get_class($this)));
        }
    }

    function setClass($class)
    {
        $this->class = $class;
    }

    function getClass()
    {
        return $this->class;
    }

    function hasParams()
    {
        return count($this->params) > 0;
    }

    function getParams()
    {
        return $this->params;
    }
}
