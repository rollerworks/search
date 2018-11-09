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

namespace Rollerworks\Component\Search\Doctrine\Dbal;

use Rollerworks\Component\Search\Exception\BadMethodCallException;
use Rollerworks\Component\Search\Exception\UnknownFieldException;
use Rollerworks\Component\Search\SearchCondition;

/**
 * A Doctrine DBAL ConditionGenerator generates WHERE an SQL WHERE-clause
 * based on the provided SearchCondition.
 *
 * This interface is provided for type hinting and should not be
 * used for alternative implementations.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface ConditionGenerator
{
    /**
     * Returns the generated where-clause.
     *
     * The Where-clause is wrapped inside a group so it can be safely used
     * with other conditions.
     *
     * @param string $prependQuery Prepend before the generated WHERE clause
     *                             Eg. " WHERE " or " AND ", ignored when WHERE
     *                             clause is empty.
     *
     * @return string
     */
    public function getWhereClause(string $prependQuery = ''): string;

    /**
     * Set the search field to database table-column mapping configuration.
     *
     * To map a field to more than one column use the `field-name#mapping-name`
     * notation for for the $fieldName argument.
     *
     * The `field-name` is the field name as registered in the FieldSet, the
     * `mapping-name` allows to configure a (secondary) mapping for a field.
     *
     * Caution: A field can only have multiple mappings _or_ one.
     *
     * * Omitting the `#` removes any existing mappings for that field.
     * * Registering a field without `#` first, and then setting multiple mappings
     *   for that field will reset the single mapping.
     *
     * Tip: The `mapping-name` doesn't have to be same as $column, but using a clear name
     * helps greatly with trouble shooting.
     *
     * @param string $fieldName Name of the search field as registered in the FieldSet or
     *                          `field-name#mapping-name` to configure a secondary mapping
     * @param string $column    Database table column-name
     * @param string $alias     Table alias as used in the query "u" for `FROM users AS u`
     * @param string $type      Doctrine DBAL supported type, either "string" (not "text")
     *
     * @throws UnknownFieldException  When the field is not registered in the fieldset
     * @throws BadMethodCallException When the where-clause is already generated
     *
     * @return static
     */
    public function setField(string $fieldName, string $column, string $alias = null, string $type = 'string');

    /**
     * Returns the assigned SearchCondition.
     */
    public function getSearchCondition(): SearchCondition;

    /**
     * Returns the configured field to columns mapping.
     *
     * @return array[] [field-name][mapping-name] => {QueryField}
     */
    public function getFieldsMapping(): array;
}
