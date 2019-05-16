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

namespace Rollerworks\Component\Search\ApiPlatform\DataProvider;

use ApiPlatform\Core\DataProvider\PaginatorInterface;

class PrecountedPaginator implements \IteratorAggregate, PaginatorInterface
{
    /**
     * @var array
     */
    private $currentResults;

    /**
     * @var int
     */
    private $firstResult;

    /**
     * @var int
     */
    private $maxResults;

    /**
     * @var int
     */
    private $totalResults;

    public function __construct(array $currentResults, int $firstResult, int $maxResults, int $totalResults)
    {
        $this->currentResults = $currentResults;
        $this->firstResult = $firstResult;
        $this->maxResults = $maxResults;
        $this->totalResults = $totalResults;
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentPage(): float
    {
        if (0 >= $this->maxResults) {
            return 1.;
        }

        return floor($this->firstResult / $this->maxResults) + 1.;
    }

    /**
     * {@inheritdoc}
     */
    public function getItemsPerPage(): float
    {
        return (float) $this->maxResults;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->currentResults);
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return $this->totalResults;
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
        return (float) $this->totalResults;
    }
}
