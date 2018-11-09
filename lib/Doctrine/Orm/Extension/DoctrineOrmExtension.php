<?php

declare(strict_types=1);

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
use Doctrine\ORM\EntityManagerInterface;
use Rollerworks\Component\Search\AbstractExtension;
use Rollerworks\Component\Search\Doctrine\Orm\Functions\SqlFieldConversion;
use Rollerworks\Component\Search\Doctrine\Orm\Functions\SqlValueConversion;

class DoctrineOrmExtension extends AbstractExtension
{
    /**
     * @param string[] $managerNames A list manager names for which to enable this extension
     */
    public function __construct(ManagerRegistry $registry, array $managerNames = ['default'])
    {
        foreach ($managerNames as $managerName) {
            /** @var EntityManagerInterface $manager */
            $manager = $registry->getManager($managerName);
            $emConfig = $manager->getConfiguration();

            $emConfig->addCustomStringFunction('RW_SEARCH_FIELD_CONVERSION', SqlFieldConversion::class);
            $emConfig->addCustomStringFunction('RW_SEARCH_VALUE_CONVERSION', SqlValueConversion::class);
        }
    }
}
