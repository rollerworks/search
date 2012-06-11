<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\RecordFilterBundle;

/**
 * FieldSet.
 *
 * Holds the set of filtering fields and there configuration.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class FieldSet
{
    /**
     * @var array
     */
    protected $fields = array();

    /**
     * @var string
     */
    protected $name;

    /**
     * Constructor.
     *
     * @param string $name Optional fieldSet name.
     */
    public function __construct($name = null)
    {
        $this->name = $name;
    }

    /**
     * Returns the name of the fieldSet.
     *
     * @return null|string
     */
    public function getSetName()
    {
        return $this->name;
    }

    /**
     * Set an filtering field.
     *
     * @param string       $name
     * @param FilterConfig $config
     *
     * @return self
     */
    public function set($name, FilterConfig $config)
    {
        $this->fields[$name] = $config;

        return $this;
    }

    /**
     * Replace the given filtering field.
     *
     * Same as {@see set()}, but throws an exception when there no field with the name.
     *
     * @param string       $name
     * @param FilterConfig $config
     *
     * @return self
     *
     * @throws \RuntimeException when there is no field with the given name
     */
    public function replace($name, FilterConfig $config)
    {
        if (!isset($this->fields[$name])) {
            throw new \RuntimeException(sprintf('Unable to replace none existent field: %s', $name));
        }

        $this->fields[$name] = $config;

        return $this;
    }

    /**
     * Remove the given field from the set.
     *
     * @param string $name
     *
     * @return self
     */
    public function remove($name)
    {
        if (isset($this->fields[$name])) {
            unset($this->fields[$name]);
        }

        return $this;
    }

    /**
     * Returns the configuration of the requested field.
     *
     * @param string $name
     *
     * @return FilterConfig
     *
     * @throws \RuntimeException when there is no field with the given name
     */
    public function get($name)
    {
        if (!isset($this->fields[$name])) {
            throw new \RuntimeException(sprintf('Unable to find filter field: %s', $name));
        }

        return $this->fields[$name];
    }

    /**
     * Returns all the registered fields.
     *
     * @return array [field-name] => {\Rollerworks\RecordFilterBundle\FilterConfig object})
     */
    public function all()
    {
        return $this->fields;
    }

    /**
     * Returns whether there is a field with the given name.
     *
     * @param string $name
     *
     * @return boolean
     */
    public function has($name)
    {
        return isset($this->fields[$name]);
    }
}
