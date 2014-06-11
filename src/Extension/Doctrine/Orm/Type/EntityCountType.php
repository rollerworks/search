<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
     * {@inheritDoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $conversion = $this->conversion;

        $resolver->setDefaults(array(
            'table_name' => null,
            'table_field' => null,
            'doctrine_dbal_conversion' => function () use ($conversion) {
                return $conversion;
            },
        ));
    }

    /**
     * Returns the name of the type.
     *
     * @return string The type name.
     */
    public function getName()
    {
        return 'doctrine_orm_entity_count';
    }

    /**
     * {@inheritDoc}
     */
    public function getParent()
    {
        return 'integer';
    }
}
