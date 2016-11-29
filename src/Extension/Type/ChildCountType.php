<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Extension\Doctrine\Dbal\Type;

use Rollerworks\Component\Search\AbstractFieldType;
use Rollerworks\Component\Search\Extension\Doctrine\Dbal\Conversion\ChildCountConversion;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * ItemCountType allows a parent/children-reference counting.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class ChildCountType extends AbstractFieldType
{
    protected $conversion;

    public function __construct()
    {
        $this->conversion = new ChildCountConversion();
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $conversion = $this->conversion;

        $resolver->setRequired(['table_name', 'table_column']);
        $resolver->setDefaults(
            [
                'doctrine_dbal_conversion' => function () use ($conversion) {
                    return $conversion;
                },
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'doctrine_dbal_child_count';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'integer';
    }
}
