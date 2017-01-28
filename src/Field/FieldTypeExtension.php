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

namespace Rollerworks\Component\Search\Field;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface FieldTypeExtension
{
    /**
     * Builds the type.
     *
     * This method is called after the extended type has built the type to
     * further modify it.
     *
     * @see SearchFieldType::buildType()
     *
     * @param FieldConfig $builder The config builder
     * @param array       $options The options
     */
    public function buildType(FieldConfig $builder, array $options);

    /**
     * Builds the SearchFieldView.
     *
     * This method is called after the extended type has built the view to
     * further modify it.
     *
     * @param FieldConfig     $config
     * @param SearchFieldView $view
     */
    public function buildView(FieldConfig $config, SearchFieldView $view);

    /**
     * Overrides the default options from the extended type.
     *
     * @param OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver);

    /**
     * Returns the name of the type being extended.
     *
     * @return string The name of the type being extended
     */
    public function getExtendedType(): string;
}
