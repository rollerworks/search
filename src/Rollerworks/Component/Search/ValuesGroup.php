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
    private $violations;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->groups = array();
        $this->fields = array();
        $this->violations = array();
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
    public function hasViolations()
    {
        return !empty($this->violations);
    }

    /**
     * Set whether this group has nested-values with violations.
     *
     * Actual violations are set the the {@see ValuesBag} object.
     *
     * @param boolean $violations
     *
     * @return self
     */
    public function setViolations($violations)
    {
        $this->violations = $violations;

        return $this;
    }
}
