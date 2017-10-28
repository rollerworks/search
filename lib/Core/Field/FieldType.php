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
interface FieldType
{
    /**
     * Returns the name of the parent type.
     *
     * @return string|null The name of the parent type if any, null otherwise
     */
    public function getParent(): ?string;

    /**
     * Sets the default options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options
     */
    public function configureOptions(OptionsResolver $resolver): void;

    /**
     * This configures the {@link FieldConfigInterface}.
     *
     * This method is called for each type in the hierarchy starting from the
     * top most type. Type extensions can further modify the field.
     *
     * @param FieldConfig $config
     * @param array       $options
     */
    public function buildType(FieldConfig $config, array $options): void;

    /**
     * Configures the SearchFieldView instance.
     *
     * This method is called for each type in the hierarchy starting from the
     * top most type. Type extensions can further modify the view.
     *
     * @see FieldTypeExtension::buildView()
     *
     * @param SearchFieldView $view
     * @param FieldConfig     $config
     * @param array           $options
     */
    public function buildView(SearchFieldView $view, FieldConfig $config, array $options): void;

    /**
     * Returns the prefix of the template block name for this type.
     *
     * The block prefix defaults to the underscored short class name with
     * the "Type" suffix removed (e.g. "UserIdType" => "user_id").
     *
     * @return string The prefix of the template block name
     */
    public function getBlockPrefix(): string;
}
