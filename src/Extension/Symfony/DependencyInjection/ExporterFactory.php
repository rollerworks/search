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
