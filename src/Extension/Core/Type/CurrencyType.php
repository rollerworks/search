<?php

/*
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Extension\Core\Type;

use Rollerworks\Component\Search\AbstractFieldType;
use Symfony\Component\Intl\Intl;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class CurrencyType extends AbstractFieldType
{
    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            array('choices' => Intl::getCurrencyBundle()->getCurrencyNames())
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'currency';
    }
}
