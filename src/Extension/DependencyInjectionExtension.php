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

namespace Rollerworks\Bundle\SearchBundle\Extension;

use Psr\Container\ContainerInterface;
use Rollerworks\Component\Search\Exception\InvalidArgumentException;
use Rollerworks\Component\Search\Field\FieldType;
use Rollerworks\Component\Search\SearchExtension;

/**
 * Provides a way to lazy load types from the Service container.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class DependencyInjectionExtension implements SearchExtension
{
    /**
     * @var ContainerInterface
     */
    private $typeContainer;

    /**
     * @var array[]
     */
    private $typeExtensionServices = [];

    /**
     * Constructor.
     *
     * @param ContainerInterface $typeContainer
     * @param iterable[]         typeExtensionServices
     */
    public function __construct(ContainerInterface $typeContainer, iterable $typeExtensions)
    {
        $this->typeContainer = $typeContainer;
        $this->typeExtensionServices = $typeExtensions;
    }

    /**
     * {@inheritdoc}
     */
    public function getType(string $name): FieldType
    {
        if (!$this->typeContainer->has($name)) {
            throw new InvalidArgumentException(
                sprintf('The field type "%s" is not registered with the service container.', $name)
            );
        }

        return $this->typeContainer->get($name);
    }

    /**
     * {@inheritdoc}
     */
    public function hasType(string $name): bool
    {
        return $this->typeContainer->has($name);
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeExtensions(string $name): array
    {
        $extensions = [];

        if (isset($this->typeExtensionServices[$name])) {
            foreach ($this->typeExtensionServices[$name] as $extensionId => $extension) {
                $extensions[] = $extension;

                // validate result of getExtendedType() to ensure it is consistent with the service definition
                if ($extension->getExtendedType() !== $name) {
                    throw new InvalidArgumentException(
                        sprintf('The extended type specified for the service "%s" does not match the actual extended type. Expected "%s", given "%s".',
                            $extensionId,
                            $name,
                            $extension->getExtendedType()
                        )
                    );
                }
            }
        }

        return $extensions;
    }
}
