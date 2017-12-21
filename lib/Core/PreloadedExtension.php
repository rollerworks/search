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

namespace Rollerworks\Component\Search;

use Rollerworks\Component\Search\Exception\InvalidArgumentException;
use Rollerworks\Component\Search\Field\FieldType;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class PreloadedExtension implements SearchExtension
{
    private $types = [];
    private $typeExtensions = [];

    /**
     * Constructor.
     *
     * @param FieldType[] $types          The types that the extension should support
     * @param array[]     $typeExtensions The type extensions that the extension should support
     */
    public function __construct(array $types, array $typeExtensions = [])
    {
        $this->types = $types;
        $this->typeExtensions = $typeExtensions;
    }

    /**
     * {@inheritdoc}
     */
    public function getType(string $name): FieldType
    {
        if (!isset($this->types[$name])) {
            throw new InvalidArgumentException(
                sprintf('Type "%s" can not be loaded by this extension', $name)
            );
        }

        return $this->types[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function hasType(string $name): bool
    {
        return isset($this->types[$name]);
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeExtensions(string $name): array
    {
        return $this->typeExtensions[$name] ?? [];
    }
}
