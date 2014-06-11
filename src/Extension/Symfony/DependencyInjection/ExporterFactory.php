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

use Rollerworks\Component\Search\ExporterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * ExporterFactory, provides lazy creating of new Exporters.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class ExporterFactory
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
     * Creates a new Exporter.
     *
     * @param string $format
     *
     * @return ExporterInterface
     *
     * @throws \InvalidArgumentException when there is no exporter for the given format.
     */
    public function create($format)
    {
        if (!isset($this->serviceIds[$format])) {
            throw new \InvalidArgumentException(sprintf('Enable to create exporter, format "%s" has no registered exporter.', $format));
        }

        return $this->container->get($this->serviceIds[$format]);
    }
}
