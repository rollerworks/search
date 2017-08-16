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
     * @param FieldConfig $builder
     * @param array       $options
     */
    public function buildType(FieldConfig $builder, array $options): void;

    /**
     * Builds the SearchFieldView.
     *
     * This method is called after the extended type has built the view to
     * further modify it.
     *
     * @param FieldConfig     $config
     * @param SearchFieldView $view
     */
    public function buildView(FieldConfig $config, SearchFieldView $view): void;

    /**
     * Overrides the default options from the extended type.
     *
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver): void;

    /**
     * Returns the name of the type being extended.
     *
     * @return string
     */
    public function getExtendedType(): string;
}
