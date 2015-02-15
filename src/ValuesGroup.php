<?php

/*
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search;

use Rollerworks\Component\Search\Exception\BadMethodCallException;
use Rollerworks\Component\Search\Exception\InvalidArgumentException;

/**
 * ValuesGroup.
 *
 * The ValuesGroup holds subgroups and values (per-field).
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class ValuesGroup implements \Serializable
{
    const GROUP_LOGICAL_OR = 'OR';
    const GROUP_LOGICAL_AND = 'AND';

    /**
     * @var ValuesGroup[]
     */
    private $groups = array();

    /**
     * @var ValuesBag[]
     */
    private $fields = array();

    /**
     * @var string
     */
    private $groupLogical;

    /**
     * @var bool
     */
    private $locked = false;

    /**
     * @var int|null
     */
    private $count = null;

    /**
     * Constructor.
     *
     * @param string $groupLogical
     */
    public function __construct($groupLogical = self::GROUP_LOGICAL_AND)
    {
        $this->groupLogical = $groupLogical;
    }

    /**
     * @param ValuesGroup $group
     *
     * @return self
     */
    public function addGroup(ValuesGroup $group)
    {
        if ($this->locked) {
            $this->throwLocked();
        }

        $this->groups[] = $group;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasGroups()
    {
        return !empty($this->groups);
    }

    /**
     * @param int $index
     *
     * @return ValuesGroup
     *
     * @throws InvalidArgumentException on invalid index.
     */
    public function getGroup($index)
    {
        if (!isset($this->fields[$index])) {
            throw new InvalidArgumentException(
                sprintf('Unable to get none existent group: "%d"', $index)
            );
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
     * @param int $index
     *
     * @return self
     */
    public function removeGroup($index)
    {
        if ($this->locked) {
            $this->throwLocked();
        }

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
        if ($this->locked) {
            $this->throwLocked();
        }

        $this->fields[$name] = $values;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return bool
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
     * @return ValuesBag
     *
     * @throws InvalidArgumentException
     */
    public function getField($name)
    {
        if (!isset($this->fields[$name])) {
            throw new InvalidArgumentException(
                sprintf('Unable to get none existent field: "%s"', $name)
            );
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
        if ($this->locked) {
            $this->throwLocked();
        }

        if (isset($this->fields[$name])) {
            unset($this->fields[$name]);
        }

        return $this;
    }

    /**
     * @param bool $deeper
     *
     * @return bool
     */
    public function hasErrors($deeper = false)
    {
        foreach ($this->fields as $field) {
            if ($field->hasErrors()) {
                return true;
            }
        }

        if ($deeper) {
            foreach ($this->groups as $group) {
                if ($group->hasErrors(true)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get the total number of values in fields list structure.
     *
     * @return int
     */
    public function countValues()
    {
        $count = 0;

        if (null !== $this->count) {
            return $this->count;
        }

        foreach ($this->fields as $field) {
            $count += $field->count();
        }

        if ($this->locked) {
            $this->count = $count;
        }

        return $count;
    }

    /**
     * Get the logical case of the field.
     *
     * This is either one of the following class constants value:
     * GROUP_LOGICAL_OR or GROUP_LOGICAL_AND.
     *
     * @return string
     */
    public function getGroupLogical()
    {
        return $this->groupLogical;
    }

    /**
     * Set the logical case of the field.
     *
     * This is either one of the following class constants value:
     * GROUP_LOGICAL_OR or GROUP_LOGICAL_AND.
     *
     * @param int
     *
     * @return self
     */
    public function setGroupLogical($groupLogical)
    {
        if ($this->locked) {
            $this->throwLocked();
        }

        $this->groupLogical = $groupLogical;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize(
            array(
                $this->groupLogical,
                $this->groups,
                $this->fields,
                $this->locked,
                $this->count
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);

        list(
            $this->groupLogical,
            $this->groups,
            $this->fields,
            $this->locked,
            $this->count
        ) = $data;
    }

    /**
     * Sets the ValuesGroup graph data is locked.
     *
     * After calling this method, setter methods can be no longer called.
     *
     * @param bool $locked
     *
     * @return self
     *
     * @throws BadMethodCallException when the data is locked
     */
    public function setDataLocked($locked = true)
    {
        if ($this->locked) {
            $this->throwLocked();
        }

        $this->locked = $locked;

        foreach ($this->fields as $field) {
            $field->setDataLocked();
        }

        foreach ($this->groups as $group) {
            $group->setDataLocked();
        }

        return $this;
    }

    /**
     * Returns whether the field's data is locked.
     *
     * A field with locked data is restricted to the data passed in
     * this configuration.
     *
     * @return bool Whether the data is locked.
     */
    public function isDataLocked()
    {
        return $this->locked;
    }

    protected function throwLocked()
    {
        throw new BadMethodCallException(
            'ValuesGroup setter methods cannot be accessed anymore once the data is locked.'
        );
    }
}
