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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Rollerworks\Component\Search\AbstractExtension;
use Rollerworks\Component\Search\Doctrine\Orm\Extension\Functions\AgeFunction;
use Rollerworks\Component\Search\Doctrine\Orm\Extension\Functions\CastFunction;
use Rollerworks\Component\Search\Doctrine\Orm\Extension\Functions\CountChildrenFunction;
use Rollerworks\Component\Search\Doctrine\Orm\Extension\Functions\MoneyCastFunction;
use Rollerworks\Component\Search\Extension\Doctrine\Orm\Type\BirthdayTypeExtension;
use Rollerworks\Component\Search\Extension\Doctrine\Orm\Type\ChildCountType;
use Rollerworks\Component\Search\Extension\Doctrine\Orm\Type\FieldTypeExtension;
use Rollerworks\Component\Search\Extension\Doctrine\Orm\Type\MoneyTypeExtension;

final class DoctrineOrmExtension extends AbstractExtension
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

            $emConfig->addCustomStringFunction('SEARCH_CONVERSION_CAST', CastFunction::class);
            $emConfig->addCustomNumericFunction('SEARCH_CONVERSION_AGE', AgeFunction::class);
            $emConfig->addCustomNumericFunction('SEARCH_COUNT_CHILDREN', CountChildrenFunction::class);
            $emConfig->addCustomNumericFunction('SEARCH_MONEY_AS_NUMERIC', MoneyCastFunction::class);
        }
    }

    protected function loadTypesExtensions(): array
    {
        return [
            new BirthdayTypeExtension(),
            new ChildCountType(),
            new FieldTypeExtension(),
            new MoneyTypeExtension(),
        ];
    }
}
