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
     * @throws InvalidArgumentException When no an unsupported group logical is provided
     */
    public function __construct(string $groupLogical = self::GROUP_LOGICAL_AND)
    {
        $this->setGroupLogical($groupLogical);
    }

    /**
     * @return self
     */
    public function addGroup(self $group)
    {
        $this->groups[] = $group;

        return $this;
    }

    public function hasGroups(): bool
    {
        return \count($this->groups) > 0;
    }

    /**
     * @throws InvalidArgumentException when no group exists at the given index
     */
    public function getGroup(int $index): self
    {
        if (! isset($this->groups[$index])) {
            throw new InvalidArgumentException(
                \sprintf('Unable to get none existent group: "%d"', $index)
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
     * @return self
     */
    public function addField(string $name, ValuesBag $values)
    {
        $this->fields[$name] = $values;

        return $this;
    }

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
     * @throws InvalidArgumentException
     */
    public function getField(string $name): ValuesBag
    {
        if (! isset($this->fields[$name])) {
            throw new InvalidArgumentException(
                \sprintf('Unable to get none existent field: "%s"', $name)
            );
        }

        return $this->fields[$name];
    }

    /**
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
     * Gets the total number of values in the fields list structure.
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
     * This is either one of the following class constants:
     * GROUP_LOGICAL_OR or GROUP_LOGICAL_AND.
     */
    public function getGroupLogical(): string
    {
        return $this->groupLogical;
    }

    /**
     * This is either one of the following class constants:
     * GROUP_LOGICAL_OR or GROUP_LOGICAL_AND.
     *
     * @return self
     *
     * @throws InvalidArgumentException When an unsupported group logical is provided
     */
    public function setGroupLogical(string $groupLogical)
    {
        if (! \in_array($groupLogical, [self::GROUP_LOGICAL_OR, self::GROUP_LOGICAL_AND], true)) {
            throw new InvalidArgumentException(\sprintf('Unsupported group logical "%s".', $groupLogical));
        }

        $this->groupLogical = $groupLogical;

        return $this;
    }

    public function __serialize(): array
    {
        return [
            'groupLogical' => $this->groupLogical,
            'groups' => $this->groups,
            'fields' => $this->fields,
        ];
    }

    public function __unserialize(array $data): void
    {
        [
            'groupLogical' => $this->groupLogical,
            'groups' => $this->groups,
            'fields' => $this->fields,
        ] = $data;
    }

    public function serialize(): string
    {
        return serialize($this->__serialize());
    }

    public function unserialize($data): void
    {
        $this->__unserialize(unserialize($data));
    }
}
