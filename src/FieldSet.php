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
use Rollerworks\Component\Search\Exception\UnexpectedTypeException;

/**
 * FieldSet holds all the search fields and there configuration.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class FieldSet implements \Countable, \IteratorAggregate
{
    /**
     * @var FieldConfigInterface[]
     */
    private $fields = array();

    /**
     * @var string
     */
    private $name;

    /**
     * @var bool
     */
    private $locked = false;

    /**
     * Constructor.
     *
     * @param string|null $name FieldSet name
     *
     * @throws UnexpectedTypeException   If the name is not a string or an integer.
     * @throws \InvalidArgumentException If the name contains invalid characters.
     */
    public function __construct($name = null)
    {
        self::validateName($name);

        $this->name = $name;
    }

    /**
     * Returns a new FieldSet instance.
     *
     * @param string|null $name
     *
     * @return FieldSet
     */
    public static function create($name = null)
    {
        return new static($name);
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
     * Set a search field.
     *
     * @param string               $name
     * @param FieldConfigInterface $config
     *
     * @return self
     */
    public function set($name, FieldConfigInterface $config)
    {
        if ($this->locked) {
            $this->throwLocked();
        }

        self::validateName($name);

        $this->fields[$name] = $config;

        return $this;
    }

    /**
     * Replaces a search field.
     *
     * Same as {@link FieldSet::set()}, but throws an exception when there is no field with that name.
     *
     * @param string               $name
     * @param FieldConfigInterface $config
     *
     * @return self
     *
     * @throws \RuntimeException When the field is not registered at this fieldset.
     */
    public function replace($name, FieldConfigInterface $config)
    {
        if ($this->locked) {
            $this->throwLocked();
        }

        if (!isset($this->fields[$name])) {
            throw new \RuntimeException(
                sprintf('Unable to replace none existent field: %s', $name)
            );
        }

        $this->fields[$name] = $config;

        return $this;
    }

    /**
     * Removes the field from this FieldSet.
     *
     * @param string $name
     *
     * @return self
     */
    public function remove($name)
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
     * Returns the {@link FieldConfigInterface} object of the search field.
     *
     * @param string $name
     *
     * @return FieldConfigInterface
     *
     * @throws \RuntimeException When the field is not registered at this fieldset.
     */
    public function get($name)
    {
        if (!isset($this->fields[$name])) {
            throw new \RuntimeException(
                sprintf('Unable to find filter field: %s', $name)
            );
        }

        return $this->fields[$name];
    }

    /**
     * Returns all the registered fields.
     *
     * @return FieldConfigInterface[] [name] => {FieldConfigInterface object})
     */
    public function all()
    {
        return $this->fields;
    }

    /**
     * Returns whether the field is registered at this FieldSet.
     *
     * @param string $name
     *
     * @return bool
     */
    public function has($name)
    {
        return isset($this->fields[$name]);
    }

    /**
     * Returns the current FieldSet as an Iterator that includes all Fields.
     *
     * @see all()
     *
     * @return \ArrayIterator An \ArrayIterator object for iterating over fields.
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->fields);
    }

    /**
     * Returns the number of Fields in this set.
     *
     * @return int The number of fields
     */
    public function count()
    {
        return count($this->fields);
    }

    /**
     * Validates whether the given variable is a valid fieldset name.
     *
     * @param string|null $name The tested fieldset name.
     *
     * @throws UnexpectedTypeException   If the name is not a string or an integer.
     * @throws \InvalidArgumentException If the name contains invalid characters.
     */
    public static function validateName($name)
    {
        if (null !== $name && !is_string($name)) {
            throw new UnexpectedTypeException($name, 'string or null');
        }

        if (!self::isValidName($name)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'The name "%s" contains illegal characters. Names should start with a letter, digit or underscore '.
                    'and only contain letters, digits, numbers, underscores ("_"), hyphens ("-") and colons (":").',
                    $name
                )
            );
        }
    }

    /**
     * Returns whether the given variable contains a valid name.
     *
     * A name is accepted if it
     *
     * * is empty
     * * starts with a letter, digit or underscore
     * * contains only letters, digits, numbers, underscores ("_"),
     * hyphens ("-") and colons (":")
     *
     * @param string $name The tested name.
     *
     * @return bool Whether the name is valid.
     */
    final public static function isValidName($name)
    {
        return '' === $name || null === $name || preg_match('/^[a-zA-Z0-9_][a-zA-Z0-9_\-:]*$/D', $name);
    }

    /**
     * Marks the FieldSet's data is locked.
     *
     * After calling this method, setter methods can be no longer called.
     *
     * @throws BadMethodCallException when the data is locked
     */
    public function lockConfig()
    {
        if ($this->locked) {
            $this->throwLocked();
        }

        $this->locked = true;
    }

    /**
     * Returns whether the FieldSet's data is locked.
     *
     * A FieldSet with locked data is restricted to the data passed in
     * this configuration.
     *
     * @return bool Whether the data is locked.
     */
    public function isConfigLocked()
    {
        return $this->locked;
    }

    private function throwLocked()
    {
        throw new BadMethodCallException(
            'FieldSet setter methods cannot be accessed anymore once the data is locked.'
        );
    }
}
