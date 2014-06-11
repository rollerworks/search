<?php

/**
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Doctrine\Orm;

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
     * @return object
     */
    public function getQuery();

    /**
     * @return SearchConditionInterface
     */
    public function getSearchCondition();

    /**
     * Returns the parameters that where set during the generation process.
     *
     * @return array
     */
    public function getParameters();

    /**
     * Returns the Query hint name for the final query object.
     *
     * The Query hint is used for conversions.
     *
     * @return string
     */
    public function getQueryHintName();

    /**
     * Returns the Query hint value for the final query object.
     *
     * The Query hint is used for conversions for value-matchers.
     *
     * @return \Closure
     */
    public function getQueryHintValue();

}
