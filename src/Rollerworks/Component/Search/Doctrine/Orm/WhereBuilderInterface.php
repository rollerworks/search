<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search\Doctrine\Orm;

/*
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
use Rollerworks\Component\Search\SearchConditionInterface;

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
