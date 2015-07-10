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

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface FieldTypeInterface
{
    /**
     * Returns the name of the type.
     *
     * @return string The type name.
     */
    public function getName();

    /**
     * Returns the name of the parent type.
     *
     * @return string|null The name of the parent type if any, null otherwise
     */
    public function getParent();

    /**
     * Sets the default options for this type.
     *
     * @param OptionsResolver $resolver The resolver for the options
     *
     * @return
     */
    public function configureOptions(OptionsResolver $resolver);

    /**
     * This configures the {@link FieldConfigInterface}.
     *
     * This method is called for each type in the hierarchy starting from the
     * top most type. Type extensions can further modify the field.
     *
     * @param FieldConfigInterface $config
     * @param array                $options
     */
    public function buildType(FieldConfigInterface $config, array $options);

    /**
     * Configures the SearchFieldView instance.
     *
     * This method is called for each type in the hierarchy starting from the
     * top most type. Type extensions can further modify the view.
     *
     * @see FieldTypeExtensionInterface::buildView()
     *
     * @param SearchFieldView      $view
     * @param FieldConfigInterface $config
     * @param array                $options
     */
    public function buildView(SearchFieldView $view, FieldConfigInterface $config, array $options);
}
