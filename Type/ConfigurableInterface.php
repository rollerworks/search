<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * ConfigurableInterface.
 *
 * An filter-type can implement this to provide configuring the type.
 * This uses the Symfony OptionsResolver component.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * @api
 */
interface ConfigurableInterface
{
    /**
     * Sets the options configuration for the resolver.
     *
     * @param OptionsResolverInterface $resolver
     *
     * @api
     */
    public static function setOptions(OptionsResolverInterface $resolver);
}
