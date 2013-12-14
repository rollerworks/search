<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
    public function __construct(ManagerRegistry $registry, $managerNames = array('default'))
    {
        foreach ((array) $managerNames as $managerName) {
            $emConfig = $registry->getManager($managerName)->getConfiguration();
            /** @var \Doctrine\ORM\Configuration $emConfig */
            $emConfig->addCustomStringFunction('RW_SEARCH_FIELD_CONVERSION', 'Rollerworks\Component\Search\Doctrine\Orm\Functions\SqlFieldConversion');
            $emConfig->addCustomStringFunction('RW_SEARCH_VALUE_CONVERSION', 'Rollerworks\Component\Search\Doctrine\Orm\Functions\SqlValueConversion');
            $emConfig->addCustomStringFunction('RW_SEARCH_MATCH', 'Rollerworks\Component\Search\Doctrine\Orm\Functions\ValueMatch');
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function loadTypes()
    {
        return array(
            new Type\EntityCountType(),
        );
    }
}
