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

use Rollerworks\Component\Search\Field\FieldConfig;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface SearchFactory
{
    /**
     * Create a new FieldSet instance with the configurator name
     * as FieldSet name.
     *
     * @param string|FieldSetConfigurator $configurator Configurator for building the FieldSet,
     *                                                  a string will be resolved to a configurator
     */
    public function createFieldSet($configurator): FieldSet;

    /**
     * @param string $name    Name of the field
     * @param string $type    Type of the field
     * @param array  $options Array of options for building the field
     */
    public function createField(string $name, string $type, array $options = []): FieldConfig;

    public function createFieldSetBuilder(): FieldSetBuilder;

    public function getSerializer(): SearchConditionSerializer;
}
