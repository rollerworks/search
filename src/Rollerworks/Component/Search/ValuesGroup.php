<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search;

/**
 * ValuesGroup.
 *
 * The ValuesGroup holds subgroups and values (per-field).
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class ValuesGroup
{
    /**
     * @var ValuesGroup[]
     */
    private $groups;

    /**
     * @var ValuesBag[]
     */
    private $fields;

    /**
     * @var array
     */
    private $errors;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->groups = array();
        $this->fields = array();
        $this->errors = array();
    }

    /**
     * @param ValuesGroup $group
     *
     * @return self
     */
    public function addGroup(ValuesGroup $group)
    {
        $this->groups[] = $group;

        return $this;
    }

    /**
     * @return boolean
     */
    public function hasGroups()
    {
        return !empty($this->groups);
    }

    /**
     * @param integer $index
     *
     * @return ValuesGroup
     *
     * @throws \InvalidArgumentException on invalid index.
     */
    public function getGroup($index)
    {
        if (!isset($this->fields[$index])) {
            throw new \InvalidArgumentException(sprintf('Unable to get none existent group: "%d"', $index));
        }

        return $this->groups[$index];
    }

    /**
     * @return ValuesGroup[]
     */
    public function getGroups()
    {
        return $this->groups;
    }

    /**
     * @param integer $index
     *
     * @return self
     */
    public function removeGroup($index)
    {
        if (isset($this->groups[$index])) {
            unset($this->groups[$index]);
        }

        return $this;
    }

    /**
     * @param string    $name
     * @param ValuesBag $values
     *
     * @return self
     */
    public function addField($name, ValuesBag $values)
    {
        $this->fields[$name] = $values;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return boolean
     */
    public function hasField($name)
    {
        return isset($this->fields[$name]);
    }

    /**
     * @return array|ValuesBag[]
     */
    public function getFields()
    {
        return $this->fields;
    }

    public function getField($name)
    {
        if (!isset($this->fields[$name])) {
            throw new \InvalidArgumentException(sprintf('Unable to get none existent field: "%s"', $name));
        }

        return $this->fields[$name];
    }

    /**
     * @param string $name
     *
     * @return self
     */
    public function removeField($name)
    {
        if (isset($this->fields[$name])) {
            unset($this->fields[$name]);
        }

        return $this;
    }

    /**
     * @return boolean
     */
    public function hasErrors()
    {
        return !empty($this->errors);
    }

    /**
     * Set whether this group has nested-values with errors.
     *
     * Actual errors are set on the {@see ValuesBag} object.
     *
     * @param boolean $errors
     *
     * @return self
     */
    public function setHasErrors($errors)
    {
        $this->errors = $errors;

        return $this;
    }
}
