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

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface SearchFactoryInterface
{
    /**
     * Create a new FieldSet instance with the configurator name
     * as FieldSet name.
     *
     * @param string|FieldSetConfiguratorInterface $configurator Configurator for building the FieldSet,
     *                                                           a string will be resolved to a configurator
     *
     * @return FieldSet
     */
    public function createFieldSet($configurator);

    /**
     * Create a new search field.
     *
     * @param string $name    Name of the field
     * @param string $type    Type of the field
     * @param array  $options Array of options for building the field
     *
     * @return FieldConfigInterface
     */
    public function createField($name, string $type, array $options = []);

    /**
     * Create a new FieldSetBuilderInterface instance.
     *
     * @return FieldSetBuilderInterface
     */
    public function createFieldSetBuilder();

    /**
     * Get the SearchConditionSerializer.
     *
     * @return SearchConditionSerializer
     */
    public function getSerializer(): SearchConditionSerializer;
}
