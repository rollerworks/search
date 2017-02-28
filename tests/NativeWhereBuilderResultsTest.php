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

use Doctrine\ORM\Query\ResultSetMappingBuilder;

final class NativeWhereBuilderResultsTest extends WhereBuilderResultsTestCase
{
    protected function getQuery()
    {
        $query = <<<'SQL'
SELECT
    I.*
FROM
    invoices AS I
JOIN
    customers AS C
    ON I.customer = C.id
LEFT JOIN
    invoice_rows AS R
    ON R.invoice = I.invoice_id
LEFT JOIN
    invoices AS IP
    ON IP.parent_id = I.invoice_id
SQL;

        $rsm = new ResultSetMappingBuilder($this->em);
        $rsm->addRootEntityFromClassMetadata(
            'Rollerworks\Component\Search\Tests\Doctrine\Orm\Fixtures\Entity\ECommerceInvoice',
            'I'
        );

        $rsm->addJoinedEntityFromClassMetadata(
            'Rollerworks\Component\Search\Tests\Doctrine\Orm\Fixtures\Entity\ECommerceInvoiceRow',
            'R',
            'I',
            'rows',
            ['label' => 'rows_label']
        );

        $rsm->addJoinedEntityFromClassMetadata(
            'Rollerworks\Component\Search\Tests\Doctrine\Orm\Fixtures\Entity\ECommerceCustomer',
            'C',
            'I',
            'customer',
            ['id' => 'customer_id']
        );

        return $this->em->createNativeQuery($query, $rsm);
    }
}
