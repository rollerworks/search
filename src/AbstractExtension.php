<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search;

use Rollerworks\Component\Search\Exception\InvalidArgumentException;
use Rollerworks\Component\Search\Exception\UnexpectedTypeException;

/**
 * The AbstractExtension can be used as a base class for SearchExtensions.
 *
 * An added bonus for extending this class rather then the implementing the the
 * {@link SearchExtensionInterface} is that any new methods added the
 * SearchExtensionInterface will not break existing implementations.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
abstract class AbstractExtension implements SearchExtensionInterface
{
    /**
     * The types provided by this extension.
     *
     * @var FieldTypeInterface[]
     */
    private $types;

    /**
     * The type extensions provided by this extension.
     *
     * Keeps an array of FieldTypeExtensionInterface objects per type-name.
     * type-name => FieldTypeExtensionInterface[]
     *
     * @var array<FieldTypeExtensionInterface[]>
     */
    private $typeExtensions;

    /**
     * {@inheritdoc}
     */
    public function getType($name)
    {
        if (null === $this->types) {
            $this->initTypes();
        }

        if (!isset($this->types[$name])) {
            throw new InvalidArgumentException(
                sprintf('The type "%s" can not be loaded by this extension', $name)
            );
        }

        return $this->types[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function hasType($name)
    {
        if (null === $this->types) {
            $this->initTypes();
        }

        return isset($this->types[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeExtensions($name)
    {
        if (null === $this->typeExtensions) {
            $this->initTypeExtensions();
        }

        return isset($this->typeExtensions[$name]) ? $this->typeExtensions[$name] : [];
    }

    /**
     * {@inheritdoc}
     */
    public function hasTypeExtensions($name)
    {
        if (null === $this->typeExtensions) {
            $this->initTypeExtensions();
        }

        return isset($this->typeExtensions[$name]) && count($this->typeExtensions[$name]) > 0;
    }

    /**
     * Registers the types.
     *
     * @return FieldTypeInterface[] an array of FormTypeInterface instances
     */
    protected function loadTypes()
    {
        return [];
    }

    /**
     * Registers the type extensions.
     *
     * @return array<FieldTypeExtensionInterface[]> an array of FieldTypeExtensionInterface instances
     *                                              per type name
     */
    protected function loadTypeExtensions()
    {
        return [];
    }

    /**
     * Initializes the types.
     *
     * @throws UnexpectedTypeException if any registered type is not an instance of FormTypeInterface
     */
    private function initTypes()
    {
        $this->types = [];

        foreach ($this->loadTypes() as $type) {
            if (!$type instanceof FieldTypeInterface) {
                throw new UnexpectedTypeException($type, 'Rollerworks\Component\Search\FieldTypeInterface');
            }

            $this->types[$type->getName()] = $type;
        }
    }

    /**
     * Initializes the type extensions.
     *
     * @throws UnexpectedTypeException if any registered type extension is not
     *                                 an instance of FieldTypeExtensionInterface
     */
    private function initTypeExtensions()
    {
        $this->typeExtensions = [];

        foreach ($this->loadTypeExtensions() as $extension) {
            if (!$extension instanceof FieldTypeExtensionInterface) {
                throw new UnexpectedTypeException(
                    $extension,
                    'Rollerworks\Component\Search\FieldTypeExtensionInterface'
                );
            }

            $type = $extension->getExtendedType();

            $this->typeExtensions[$type][] = $extension;
        }
    }
}
