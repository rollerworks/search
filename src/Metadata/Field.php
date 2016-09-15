<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Metadata;

use Rollerworks\Component\Search\Exception\BadMethodCallException;
use Rollerworks\Component\Search\Exception\InvalidArgumentException;

/**
 * Annotation class for search fields.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * @Annotation
 * @Target({"PROPERTY"})
 */
class Field
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $type;

    /**
     * @var array
     */
    private $options = [];

    /**
     * Constructor.
     *
     * @param array $data An array of key/value parameters
     *
     * @throws BadMethodCallException
     * @throws InvalidArgumentException
     */
    public function __construct(array $data)
    {
        $this->name = null;
        $this->type = null;
        $this->required = false;
        $this->options = [];

        if (isset($data['value'])) {
            $data['name'] = $data['value'];
            unset($data['value']);
        }

        foreach ($data as $key => $value) {
            $method = 'set'.ucfirst($key);

            if (!method_exists($this, $method)) {
                throw new BadMethodCallException(
                    sprintf('Unknown property "%s" on annotation "%s".', $key, get_class($this))
                );
            }

            $this->$method($value);
        }

        if (null === $this->name) {
            throw new InvalidArgumentException(
                sprintf('Property "%s" on annotation "%s" is required.', 'name', get_class($this))
            );
        }

        if (null === $this->type) {
            throw new InvalidArgumentException(
                sprintf('Property "%s" on annotation "%s" is required.', 'type', get_class($this))
            );
        }
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @deprecated Deprecated since version 1.0.0-beta5, to be removed in 2.0.
     *             Use a custom validator instead
     */
    public function setRequired($required)
    {
    }

    /**
     * @deprecated Deprecated since version 1.0.0-beta5, to be removed in 2.0.
     *             Use a custom validator instead
     */
    public function isRequired()
    {
        return false;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param array $options
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }
}
