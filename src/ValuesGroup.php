<?php

/**
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search;

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
     * @var bool
     */
    private $errors = false;

    /**
     * @var string
     */
    private $groupLogical;

    /**
     * Constructor.
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
        if (isset($this->fields[$name])) {
            unset($this->fields[$name]);
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function hasErrors()
    {
        return $this->errors;
    }

    /**
     * Set whether this group has nested-values with errors.
     *
     * Actual errors are set on the {@see ValuesBag} object.
     *
     * @param bool $errors
     *
     * @return self
     */
    public function setHasErrors($errors = true)
    {
        $this->errors = $errors;

        return $this;
    }

    /**
     * Get the logical case of the field.
     *
     * This is either one of the following class constants value:
     * GROUP_LOGICAL_OR or GROUP_LOGICAL_AND
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
     * GROUP_LOGICAL_OR or GROUP_LOGICAL_AND
     *
     * @param int
     */
    public function setGroupLogical($groupLogical)
    {
        $this->groupLogical = $groupLogical;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize(array(
            $this->groupLogical,
            $this->groups,
            $this->fields,
            $this->errors
        ));
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
            $this->errors
        ) = $data;
    }
}
