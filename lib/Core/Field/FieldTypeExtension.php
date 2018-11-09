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
     * This method is called after the extended type has built the type to
     * further modify it.
     *
     * @see SearchFieldType::buildType()
     */
    public function buildType(FieldConfig $builder, array $options): void;

    /**
     * This method is called after the extended type has built the view to
     * further modify it.
     */
    public function buildView(FieldConfig $config, SearchFieldView $view): void;

    /**
     * Overrides the default options from the extended type.
     */
    public function configureOptions(OptionsResolver $resolver): void;

    /**
     * @return string FQCN of the type class
     */
    public function getExtendedType(): string;
}
