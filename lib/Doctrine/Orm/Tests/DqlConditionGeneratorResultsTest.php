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

/**
 * @internal
 */
final class DqlConditionGeneratorResultsTest extends ConditionGeneratorResultsTestCase
{
    protected function getQuery()
    {
        $query = <<<'DQL'
SELECT
    I
FROM
    Rollerworks\Component\Search\Tests\Doctrine\Orm\Fixtures\Entity\ECommerceInvoice AS I
JOIN
    I.customer AS C
LEFT JOIN
    I.children AS IP
LEFT JOIN
    I.rows AS R
DQL;

        return $this->em->createQuery($query);
    }
}
