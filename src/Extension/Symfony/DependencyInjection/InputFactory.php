<?php

/**
 * This file is part of RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Extension\Symfony\DependencyInjection;

use Rollerworks\Component\Search\InputProcessorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * InputFactory, provides lazy creating of new Input processors.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class InputFactory
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var array
     */
    private $serviceIds;

    /**
     * @param ContainerInterface $container
     * @param array              $serviceIds
     */
    public function __construct(ContainerInterface $container, array $serviceIds)
    {
        $this->container = $container;
        $this->serviceIds = $serviceIds;
    }

    /**
     * Creates a new Input processor.
     *
     * @param string $name
     *
     * @return InputProcessorInterface
     *
     * @throws \InvalidArgumentException when there is no input processor with the given name.
     */
    public function create($name)
    {
        if (!isset($this->serviceIds[$name])) {
            throw new \InvalidArgumentException(sprintf('Enable to create input-processor, "%s" is not registered as processor.', $name));
        }

        return $this->container->get($this->serviceIds[$name]);
    }
}
