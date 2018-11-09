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
use Rollerworks\Component\Search\Field\FieldConfig;

/**
 * A FieldSet holds all the search fields and there configuration.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface FieldSet
{
    /**
     * Returns the name of the set.
     */
    public function getSetName(): ?string;

    /**
     * Returns the field as a {@link FieldConfig} instance.
     *
     * @throws UnknownFieldException When the field is not registered at this Fieldset
     */
    public function get(string $name): FieldConfig;

    /**
     * Returns all the registered fields in the set.
     *
     * @return FieldConfig[] [name] => {FieldConfig instance})
     */
    public function all(): array;

    /**
     * Returns whether the field is registered in the set.
     */
    public function has(string $name): bool;

    /**
     * Returns whether the field is a private field (primary condition only).
     */
    public function isPrivate(string $name): bool;
}
