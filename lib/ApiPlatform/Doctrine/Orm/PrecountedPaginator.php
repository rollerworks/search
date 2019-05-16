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

namespace Rollerworks\Component\Search\ApiPlatform\Doctrine\Orm;

use ApiPlatform\Core\Bridge\Doctrine\Orm\AbstractPaginator;
use ApiPlatform\Core\DataProvider\PaginatorInterface;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;

class PrecountedPaginator extends AbstractPaginator implements PaginatorInterface
{
    /**
     * @var int
     */
    private $totalItems;

    public function __construct(DoctrinePaginator $paginator, int $totalItems)
    {
        parent::__construct($paginator);

        $this->totalItems = $totalItems;
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return $this->totalItems;
    }

    /**
     * {@inheritdoc}
     */
    public function getLastPage(): float
    {
        if (0 >= $this->maxResults) {
            return 1.;
        }

        return ceil($this->getTotalItems() / $this->maxResults) ?: 1.;
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalItems(): float
    {
        return (float) $this->totalItems;
    }
}
