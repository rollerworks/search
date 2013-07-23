<?php

/*
 * This file is part of the RollerworksRecordFilterBundle package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * ConfigurableTypeInterface.
 *
 * A filter-type can implement this to provide configuration for the type.
 * This uses the Symfony OptionsResolver component.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * @api
 */
interface ConfigurableTypeInterface
{
    /**
     * Sets the options configuration for the resolver.
     *
     * @param OptionsResolverInterface $resolver
     *
     * @api
     */
    public static function setDefaultOptions(OptionsResolverInterface $resolver);

    /**
     * Sets the options for the type.
     *
     * @param array $options
     *
     * @api
     */
    public function setOptions(array $options);

    /**
     * Returns the current options of the type.
     *
     * The values must be exportable, resources will throw an exception.
     *
     * @return array
     *
     * @api
     */
    public function getOptions();
}
