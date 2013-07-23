<?php

/*
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Factory;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Rollerworks\Bundle\RecordFilterBundle\Type\FilterTypeInterface;
use Rollerworks\Bundle\RecordFilterBundle\Type\ConfigurableTypeInterface;

/**
 * The FilterTypeFactory holds a collection of filter-type references.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class FilterTypeFactory
{
    protected $container;
    protected $types;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container The service container
     * @param array              $types     An associative array of filter-types
     */
    public function __construct(ContainerInterface $container, array $types = array())
    {
        $this->container = $container;
        $this->types = $types;
    }

    /**
     * Returns the a new FilterType instance.
     *
     * @param string $type    Name of the filter-type
     * @param array  $options Options to pass to the filter-type (only when accepted)
     *
     * @return FilterTypeInterface
     *
     * @throws \InvalidArgumentException When the filter-type is not registered
     */
    public function newInstance($type, array $options = array())
    {
        if (!isset($this->types[$type]) && !$this->container->has($type)) {
            throw new \InvalidArgumentException(sprintf('No such type registered "%s".', $type));
        }

        if (isset($this->types[$type])) {
            $instance = $this->container->get($this->types[$type]);
        } else {
            $instance = $this->container->get($type);
        }

        if (array() !== $options && $instance instanceof ConfigurableTypeInterface) {
            $instance->setOptions($options);
        }

        return $instance;
    }
}
