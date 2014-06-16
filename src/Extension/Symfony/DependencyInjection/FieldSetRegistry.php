<?php

/**
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Extension\Symfony\DependencyInjection;

use Rollerworks\Component\Search\FieldSet;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class FieldSetRegistry
{
    /**
     * @var string[]
     */
    private $serviceIds = array();

    /**
     * Constructor.
     *
     * @param ContainerInterface $container
     * @param array              $serviceIds
     */
    public function __construct(ContainerInterface $container, array $serviceIds)
    {
        $this->container = $container;
        $this->serviceIds = $serviceIds;
    }

    /**
     * Gets a FieldSet from the container.
     *
     * @param string $name
     *
     * @return FieldSet
     *
     * @throws \InvalidArgumentException
     */
    public function getFieldSet($name)
    {
        if (!isset($this->serviceIds[$name])) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Unable to get FieldSet "%s", FieldSet does not seem to be registered in the Service Container.',
                    $name
                )
            );
        }

        return $this->container->get($this->serviceIds[$name]);
    }
}
