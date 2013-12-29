<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
