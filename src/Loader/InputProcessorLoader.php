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
use Rollerworks\Component\Search\Exception\InvalidArgumentException;
use Rollerworks\Component\Search\Input;
use Rollerworks\Component\Search\InputProcessor;

/**
 * InputProcessorLoader provides lazy loading of Input processors.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class InputProcessorLoader
{
    private $container;
    private $serviceIds;

    /**
     * Constructor.
     *
     * @param ContainerInterface $container  A PSR-11 compatible Service locator/container
     * @param string[]           $serviceIds Format alias to service-id mapping,
     *                                       eg. 'json' => 'JsonInput-ClassName'
     */
    public function __construct(ContainerInterface $container, array $serviceIds)
    {
        $this->container = $container;
        $this->serviceIds = $serviceIds;
    }

    /**
     * Create a new InputProcessorLoader with the build-in InputProcessors
     * loadable.
     *
     * @param Input\Validator|null $validator
     *
     * @return InputProcessorLoader
     */
    public static function create(Input\Validator $validator = null): InputProcessorLoader
    {
        return new self(
            new ClosureContainer(
                [
                    'rollerworks_search.input.array' => function () use ($validator) {
                        return new Input\ArrayInput($validator);
                    },
                    'rollerworks_search.input.json' => function () use ($validator) {
                        return new Input\JsonInput($validator);
                    },
                    'rollerworks_search.input.xml' => function () use ($validator) {
                        return new Input\XmlInput($validator);
                    },
                    'rollerworks_search.input.string_query' => function () use ($validator) {
                        return new Input\StringQueryInput($validator);
                    },
                ]
            ),
            [
                'array' => 'rollerworks_search.input.array',
                'json' => 'rollerworks_search.input.json',
                'xml' => 'rollerworks_search.input.xml',
                'string_query' => 'rollerworks_search.input.string_query',
            ]
        );
    }

    /**
     * Lazily loads an Input processor.
     *
     * @param string $name
     *
     * @throws \InvalidArgumentException when there is no input processor with the given name
     *
     * @return InputProcessor
     */
    public function get(string $name): InputProcessor
    {
        if (!isset($this->serviceIds[$name])) {
            throw new InvalidArgumentException(
                sprintf('Enable to load input-processor, "%s" is not registered as processor.', $name)
            );
        }

        return $this->container->get($this->serviceIds[$name]);
    }
}
