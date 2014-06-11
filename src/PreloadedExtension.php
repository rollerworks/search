<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search;

use Rollerworks\Component\Search\Exception\InvalidArgumentException;

class PreloadedExtension implements SearchExtensionInterface
{
    /**
     * @var array
     */
    private $types = array();

    /**
     * @var array
     */
    private $typeExtensions = array();

    /**
     * Creates a new preloaded extension.
     *
     * @param FieldTypeInterface[]          $types          The types that the extension should support.
     * @param FieldTypeExtensionInterface[] $typeExtensions The type extensions that the extension should support.
     */
    public function __construct(array $types, array $typeExtensions)
    {
        $this->types = $types;
        $this->typeExtensions = $typeExtensions;
    }

    /**
     * {@inheritdoc}
     */
    public function getType($name)
    {
        if (!isset($this->types[$name])) {
            throw new InvalidArgumentException(sprintf('The type "%s" can not be loaded by this extension', $name));
        }

        return $this->types[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function hasType($name)
    {
        return isset($this->types[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeExtensions($name)
    {
        return isset($this->typeExtensions[$name])
            ? $this->typeExtensions[$name]
            : array();
    }

    /**
     * {@inheritdoc}
     */
    public function hasTypeExtensions($name)
    {
        return !empty($this->typeExtensions[$name]);
    }
}
