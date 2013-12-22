<?php

/*
 * This file is part of the RollerworksSearchBundle package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Rollerworks\Bundle\SearchBundle\DependencyInjection\Factory;

class FieldSet
{
    private $fields = array();
    private $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function set($name, $type, $modelClass, $modelProperty, $required, array $options)
    {
        $this->fields[$name] = array(
            'type' => $type,
            'model_class' => $modelClass,
            'model_property' => $modelProperty,
            'required' => $required,
            'options' => $options,
        );

        return $this;
    }

    public function get($name)
    {
        if (!isset($this->fields[$name])) {
            throw new \InvalidArgumentException('Field "%s" is not registered in the FieldSet.', $name);
        }

        return $this->fields[$name];
    }

    public function has($name)
    {
        return isset($this->fields[$name]);
    }

    public function remove($name)
    {
        if (isset($this->fields[$name])) {
            unset($this->fields[$name]);
        }

        return $this;
    }

    public function all()
    {
        return $this->fields;
    }

    public function getName()
    {
        return $this->name;
    }
}
