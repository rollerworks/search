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

use Rollerworks\Component\Search\Exception\UnknownFieldException;

/**
 * A FieldSet holds all the search fields and there configuration.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class FieldSet implements \IteratorAggregate
{
    private $fields = [];
    private $name;

    /**
     * Constructor.
     *
     * @param FieldConfigInterface[] $fields
     * @param string|null            $name FQCN of the FieldSet configurator
     */
    public function __construct(array $fields, string $name = null)
    {
        $this->fields = $fields;
        $this->name = $name;
    }

    /**
     * Returns the name of the set.
     *
     * @return null|string
     */
    public function getSetName()
    {
        return $this->name;
    }

    /**
     * Returns the {@link FieldConfigInterface} object of the search field.
     *
     * @param string $name
     *
     * @throws \RuntimeException When the field is not registered at this Fieldset
     *
     * @return FieldConfigInterface
     */
    public function get(string $name)
    {
        if (!isset($this->fields[$name])) {
            throw new UnknownFieldException($name);
        }

        return $this->fields[$name];
    }

    /**
     * Returns all the registered fields in the set.
     *
     * @return FieldConfigInterface[] [name] => {FieldConfigInterface object})
     */
    public function all()
    {
        return $this->fields;
    }

    /**
     * Returns whether the field is registered in the set.
     *
     * @param string $name
     *
     * @return bool
     */
    public function has(string $name)
    {
        return isset($this->fields[$name]);
    }

    /**
     * Returns the current FieldSet as an Iterator that includes all Fields.
     *
     * @see all()
     *
     * @return \ArrayIterator An \ArrayIterator object for iterating over fields
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->fields);
    }
}
