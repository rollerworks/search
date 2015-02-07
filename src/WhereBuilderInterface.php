<?php

/*
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
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
