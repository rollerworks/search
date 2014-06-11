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

use Rollerworks\Component\Search\Exception\InvalidArgumentException;
use Rollerworks\Component\Search\SearchExtensionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a way to lazy load types from the Symfony2 Dependency Injection container.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class DependencyInjectionExtension implements SearchExtensionInterface
{
    private $container;

    private $typeServiceIds;

    private $typeExtensionServiceIds;

    public function __construct(ContainerInterface $container, array $typeServiceIds, array $typeExtensionServiceIds)
    {
        $this->container = $container;
        $this->typeServiceIds = $typeServiceIds;
        $this->typeExtensionServiceIds = $typeExtensionServiceIds;
    }

    public function getType($name)
    {
        if (!isset($this->typeServiceIds[$name])) {
            throw new InvalidArgumentException(sprintf('The field type "%s" is not registered with the service container.', $name));
        }

        $type = $this->container->get($this->typeServiceIds[$name]);

        if ($type->getName() !== $name) {
            throw new InvalidArgumentException(
                sprintf('The type name specified for the service "%s" does not match the actual name. Expected "%s", given "%s"',
                    $this->typeServiceIds[$name],
                    $name,
                    $type->getName()
                ));
        }

        return $type;
    }

    public function hasType($name)
    {
        return isset($this->typeServiceIds[$name]);
    }

    public function getTypeExtensions($name)
    {
        $extensions = array();

        if (isset($this->typeExtensionServiceIds[$name])) {
            foreach ($this->typeExtensionServiceIds[$name] as $serviceId) {
                $extensions[] = $this->container->get($serviceId);
            }
        }

        return $extensions;
    }

    public function hasTypeExtensions($name)
    {
        return isset($this->typeExtensionServiceIds[$name]);
    }
}
