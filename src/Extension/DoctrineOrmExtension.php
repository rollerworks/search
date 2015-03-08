<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Extension\Doctrine\Orm;

use Doctrine\Common\Persistence\ManagerRegistry;
use Rollerworks\Component\Search\AbstractExtension;

/**
 * Represents the doctrine ORM extension,
 * for the core Doctrine ORM functionality.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class DoctrineOrmExtension extends AbstractExtension
{
    /**
     * @param ManagerRegistry $registry
     * @param array           $managerNames
     */
    public function __construct(ManagerRegistry $registry, array $managerNames = ['default'])
    {
        foreach ($managerNames as $managerName) {
            /** @var \Doctrine\ORM\Configuration $emConfig */
            $emConfig = $registry->getManager($managerName)->getConfiguration();

            $emConfig->addCustomStringFunction(
                'RW_SEARCH_FIELD_CONVERSION',
                'Rollerworks\Component\Search\Doctrine\Orm\Functions\SqlFieldConversion'
            );

            $emConfig->addCustomStringFunction(
                'RW_SEARCH_VALUE_CONVERSION',
                'Rollerworks\Component\Search\Doctrine\Orm\Functions\SqlValueConversion'
            );

            $emConfig->addCustomStringFunction(
                'RW_SEARCH_MATCH',
                'Rollerworks\Component\Search\Doctrine\Orm\Functions\ValueMatch'
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function loadTypes()
    {
        return [
            new Type\EntityCountType(),
        ];
    }
}
