<?php

/**
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Extension\Doctrine\Orm\Type;

use Doctrine\Common\Persistence\ManagerRegistry;
use Rollerworks\Component\Search\AbstractFieldType;
use Rollerworks\Component\Search\Extension\Doctrine\Orm\Conversion\EntityCountConversion;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * EntityCountType allows a parent/children-reference counting.
 *
 * The reference-type is automatically determined using the mapping
 * information.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class EntityCountType extends AbstractFieldType
{
    /**
     * @var ManagerRegistry
     */
    protected $conversion;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->conversion = new EntityCountConversion();
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $conversion = $this->conversion;

        $resolver->setDefaults(
            array(
                'table_name' => null,
                'table_field' => null,
                'doctrine_dbal_conversion' => function () use ($conversion) {
                    return $conversion;
                },
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'doctrine_orm_entity_count';
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'integer';
    }
}
