<?php

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

/**
 * The FieldSetBuilder helps with building a {@link FieldSet}.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class FieldSetBuilder implements FieldSetBuilderInterface
{
    /**
     * @var FieldConfigInterface[]
     */
    private $fields = [];

    /**
     * @var array[]
     */
    private $unresolvedFields = [];

    /**
     * @var SearchFactoryInterface
     */
    private $searchFactory;

    public function __construct(SearchFactoryInterface $searchFactory)
    {
        $this->searchFactory = $searchFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function set(FieldConfigInterface $field)
    {
        $this->fields[$field->getName()] = $field;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function add(string $name, string $type, array $options = [])
    {
        $this->unresolvedFields[$name] = [
            'type' => $type,
            'options' => $options,
        ];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function remove($name)
    {
        unset($this->fields[$name], $this->unresolvedFields[$name]);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function has($name)
    {
        if (isset($this->unresolvedFields[$name])) {
            return true;
        }

        if (isset($this->fields[$name])) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function get($name)
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

    /**
     * {@inheritdoc}
     */
    public function getFieldSet(string $setName = null)
    {
        foreach ($this->unresolvedFields as $name => $field) {
            $this->fields[$name] = $this->searchFactory->createField(
                $name,
                $field['type'],
                $field['options']
            );

            unset($this->unresolvedFields[$name]);
        }

        return new FieldSet($this->fields, $setName);
    }
}
