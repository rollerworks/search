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

use Rollerworks\Component\Search\Exception\UnknownFieldException;

/**
 * A FieldSet holds all the search fields and there configuration.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class GenericFieldSet implements FieldSet
{
    private $fields = [];
    private $name;

    /**
     * Constructor.
     *
     * @param FieldConfig[] $fields
     * @param string|null   $name   FQCN of the FieldSet configurator
     */
    public function __construct(array $fields, string $name = null)
    {
        $this->fields = $fields;
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function getSetName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $name): FieldConfig
    {
        if (!isset($this->fields[$name])) {
            throw new UnknownFieldException($name);
        }

        return $this->fields[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function all(): array
    {
        return $this->fields;
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $name): bool
    {
        return isset($this->fields[$name]);
    }
}
