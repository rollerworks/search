<?php

declare(strict_types=1);

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Loader;

use Psr\Container\ContainerInterface;
use Rollerworks\Component\Search\ConditionExporter;
use Rollerworks\Component\Search\Exception\InvalidArgumentException;
use Rollerworks\Component\Search\Exporter;

/**
 * ConditionExporterLoader provides lazy loading of ConditionExporters.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class ConditionExporterLoader
{
    private $container;
    private $serviceIds = [];

    /**
     * @param ContainerInterface $container  A PSR-11 compatible Service locator/container
     * @param array              $serviceIds Format alias to service-id mapping,
     *                                       eg. 'json' => 'JsonExporter-ClassName'
     */
    public function __construct(ContainerInterface $container, array $serviceIds)
    {
        $this->container = $container;
        $this->serviceIds = $serviceIds;
    }

    /**
     * Create a new ConditionExporterLoader with the build-in ConditionExporters
     * loadable.
     *
     * @return ConditionExporterLoader
     */
    public static function create(): self
    {
        return new self(
            new ClosureContainer(
                [
                    'rollerworks_search.condition_exporter.json' => static function () {
                        return new Exporter\JsonExporter();
                    },
                    'rollerworks_search.condition_exporter.string_query' => static function () {
                        return new Exporter\StringQueryExporter();
                    },
                    'rollerworks_search.condition_exporter.norm_string_query' => static function () {
                        return new Exporter\NormStringQueryExporter();
                    },
                ]
            ),
            [
                'json' => 'rollerworks_search.condition_exporter.json',
                'string_query' => 'rollerworks_search.condition_exporter.string_query',
                'norm_string_query' => 'rollerworks_search.condition_exporter.norm_string_query',
            ]
        );
    }

    /**
     * @throws \InvalidArgumentException when there is no exporter for the given format
     */
    public function get(string $format): ConditionExporter
    {
        if (! isset($this->serviceIds[$format])) {
            throw new InvalidArgumentException(
                \sprintf('Enable to load exporter, format "%s" has no registered exporter.', $format)
            );
        }

        return $this->container->get($this->serviceIds[$format]);
    }
}
