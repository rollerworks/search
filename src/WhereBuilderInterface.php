<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Doctrine\Orm;

use Doctrine\ORM\NativeQuery;
use Doctrine\ORM\Query;
use Rollerworks\Component\Search\SearchConditionInterface;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface WhereBuilderInterface
{
    /**
     * Returns the generated where-clause.
     *
     * @return string
     */
    public function getWhereClause();

    /**
     * Updates the configured query object with the where-clause.
     */
    public function updateQuery();

    /**
     * @return SearchConditionInterface
     */
    public function getSearchCondition();

    /**
     * @return Query|NativeQuery
     */
    public function getQuery();
}
