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

namespace Rollerworks\Component\Search;

use Rollerworks\Component\Search\Exception\InvalidArgumentException;
use Rollerworks\Component\Search\Field\FieldConfig;

/**
 * The FieldSetBuilder helps with building a {@link FieldSet}.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class GenericFieldSetBuilder implements FieldSetBuilder
{
    /**
     * @var FieldConfig[]
     */
    private $fields = [];

    /**
     * @var array[]
     */
    private $unresolvedFields = [];

    /**
     * @var SearchFactory
     */
    private $searchFactory;

    public function __construct(SearchFactory $searchFactory)
    {
        $this->searchFactory = $searchFactory;
    }

    public function set(FieldConfig $field)
    {
        $this->fields[$field->getName()] = $field;

        return $this;
    }

    public function add(string $name, string $type, array $options = [])
    {
        $this->unresolvedFields[$name] = [
            'type' => $type,
            'options' => $options,
        ];

        return $this;
    }

    public function remove(string $name)
    {
        unset($this->fields[$name], $this->unresolvedFields[$name]);

        return $this;
    }

    public function has(string $name): bool
    {
        if (isset($this->unresolvedFields[$name])) {
            return true;
        }

        if (isset($this->fields[$name])) {
            return true;
        }

        return false;
    }

    public function get(string $name): FieldConfig
    {
        if (isset($this->unresolvedFields[$name])) {
            $this->fields[$name] = $this->searchFactory->createField(
                $name,
                $this->unresolvedFields[$name]['type'],
                $this->unresolvedFields[$name]['options']
            );

            unset($this->unresolvedFields[$name]);
        }

        if (isset($this->fields[$name])) {
            return $this->fields[$name];
        }

        throw new InvalidArgumentException(sprintf('The field with the name "%s" does not exist.', $name));
    }

    public function getFieldSet(string $setName = null): FieldSet
    {
        foreach ($this->unresolvedFields as $name => $field) {
            $this->fields[$name] = $this->searchFactory->createField(
                $name,
                $field['type'],
                $field['options']
            );

            unset($this->unresolvedFields[$name]);
        }

        return new GenericFieldSet($this->fields, $setName);
    }
}
