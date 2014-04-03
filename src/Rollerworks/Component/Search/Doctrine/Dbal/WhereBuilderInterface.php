<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search\Doctrine\Dbal;

use Doctrine\DBAL\Statement;
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
     * Returns the parameters that where set during the generation process.
     *
     * @return array
     */
    public function getParameterTypes();

    /**
     * Binds the parameters to the statement.
     *
     * @param Statement $statement
     *
     * @return null
     */
    public function bindParameters(Statement $statement);
}
