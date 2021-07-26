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

namespace Rollerworks\Component\Search\Tests\Doctrine\Orm;

use Doctrine\ORM\QueryBuilder;
use Rollerworks\Component\Search\Tests\Doctrine\Orm\Fixtures\Entity\ECommerceInvoice;

/**
 * @internal
 */
final class DqlConditionGeneratorResultsTest extends ConditionGeneratorResultsTestCase
{
    protected function getQuery(): QueryBuilder
    {
        return $this->em->createQueryBuilder()
            ->select('I')
            ->from(ECommerceInvoice::class, 'I')
            ->join('I.customer', 'C')
            ->leftJoin('I.children', 'IP')
            ->leftJoin('I.rows', 'R');
    }
}
