<?php

declare(strict_types=1);

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Value;

use Rollerworks\Component\Search\Exception\InvalidArgumentException;

/**
 * ValuesGroup.
 *
 * The ValuesGroup holds sub-groups and values (per field).
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class ValuesGroup implements \Serializable
{
    public const GROUP_LOGICAL_OR = 'OR';
    public const GROUP_LOGICAL_AND = 'AND';

    /**
     * @var ValuesGroup[]
     */
    private $groups = [];

    /**
     * @var ValuesBag[]
     */
    private $fields = [];

    /**
     * @var string
     */
    private $groupLogical;

    /**
     * Constructor.
     *
     * @param string $groupLogical
     *
     * @throws InvalidArgumentException When no an unsupported group logical is provided
     */
    public function __construct(string $groupLogical = self::GROUP_LOGICAL_AND)
    {
        $this->setGroupLogical($groupLogical);
    }

    /**
     * @param ValuesGroup $group
     *
     * @return self
     */
    public function addGroup(self $group)
    {
        $this->groups[] = $group;

        return $this;
    }

    /**
     * @return bool
     */
    public function hasGroups(): bool
    {
        return \count($this->groups) > 0;
    }

    /**
     * @param int $index
     *
     * @throws InvalidArgumentException when no group exists at the given index
     *
     * @return ValuesGroup
     */
    public function getGroup(int $index): self
    {
        if (!isset($this->groups[$index])) {
            throw new InvalidArgumentException(
                sprintf('Unable to get none existent group: "%d"', $index)
            );
        }

        return $this->groups[$index];
    }

    /**
     * @return ValuesGroup[]
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    /**
     * @param int $index
     *
     * @return self
     */
    public function removeGroup(int $index)
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
    public function addField(string $name, ValuesBag $values)
    {
        $this->fields[$name] = $values;

        return $this;
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasField(string $name): bool
    {
        return isset($this->fields[$name]);
    }

    /**
     * @return ValuesBag[]
     */
    public function getFields(): array
    {
        return $this->fields;
    }

    /**
     * @param string $name
     *
     * @throws InvalidArgumentException
     *
     * @return ValuesBag
     */
    public function getField(string $name): ValuesBag
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
    public function removeField(string $name)
    {
        if (isset($this->fields[$name])) {
            unset($this->fields[$name]);
        }

        return $this;
    }

    /**
     * Gets the total number of values
     * in the fields list structure.
     *
     * @return int
     */
    public function countValues(): int
    {
        $count = 0;

        foreach ($this->fields as $field) {
            $count += $field->count();
        }

        return $count;
    }

    /**
     * Get the logical case of the field.
     *
     * This is either one of the following class constants:
     * GROUP_LOGICAL_OR or GROUP_LOGICAL_AND.
     *
     * @return string
     */
    public function getGroupLogical(): string
    {
        return $this->groupLogical;
    }

    /**
     * Set the logical case of the ValuesGroup.
     *
     * This is either one of the following class constants:
     * GROUP_LOGICAL_OR or GROUP_LOGICAL_AND.
     *
     * @param string $groupLogical
     *
     * @throws InvalidArgumentException When an unsupported group logical is provided
     *
     * @return self
     */
    public function setGroupLogical(string $groupLogical)
    {
        if (!\in_array($groupLogical, [self::GROUP_LOGICAL_OR, self::GROUP_LOGICAL_AND], true)) {
            throw new InvalidArgumentException(sprintf('Unsupported group logical "%s".', $groupLogical));
        }

        $this->groupLogical = $groupLogical;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize(): string
    {
        return serialize(
            [
                $this->groupLogical,
                $this->groups,
                $this->fields,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized): void
    {
        $data = unserialize($serialized);

        list(
            $this->groupLogical,
            $this->groups,
            $this->fields) = $data;
    }
}
