<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Sebastiaan Stok
 * Date: 8-8-13
 * Time: 12:55
 * To change this template use File | Settings | File Templates.
 */

namespace Rollerworks\Component\Search;


use Symfony\Component\OptionsResolver\OptionsResolverInterface;

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
     * @return string|null The name of the parent type if any, null otherwise.
     */
    public function getParent();

    /**
     * Sets the default options for this type.
     *
     * @param OptionsResolverInterface $resolver The resolver for the options.
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver);

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
     * Returns whether ranges supported by this type.
     *
     * @return boolean
     */
    public function hasRangeSupport();

    /**
     * Returns whether comparisons supported by this type.
     *
     * @return boolean
     */
    public function hasCompareSupport();

    /**
     * Returns whether pattern-matchers are supported by this type.
     *
     * @return boolean
     */
    public function hasPatternMatchSupport();
}
