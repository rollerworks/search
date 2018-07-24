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
 * This interface is provided for type hinting it should not
 * be implemented in external code.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface ConditionGenerator
{
    /**
     * Returns the generated where-clause.
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
     * To map a field to more then one column use `field-name#mapping-name`
     * for the $fieldName argument. The `field-name` is the field name as registered
     * in the FieldSet, `mapping-name` allows to configure a (secondary) mapping for a field.
     *
     * Caution: A field can only have multiple mappings or one, omitting `#` will remove
     * any existing mappings for that field. Registering the field without `#` first and then
     * setting multiple mappings for that field will reset the single mapping.
     *
     * Tip: The `mapping-name` doesn't have to be same as $column, but using a clear name
     * will help with trouble shooting.
     *
     * @param string $fieldName Name of the search field as registered in the FieldSet or
     *                          `field-name#mapping-name` to configure a secondary mapping
     * @param string $column    Database table column-name
     * @param string $alias     Table alias as used in the query "u" for `FROM users AS u`
     * @param string $type      Doctrine DBAL supported type, eg. string (not text)
     *
     * @throws UnknownFieldException  When the field is not registered in the fieldset
     * @throws BadMethodCallException When the where-clause is already generated
     *
     * @return static
     */
    public function setField(string $fieldName, string $column, string $alias = null, string $type = 'string');

    /**
     * Returns the assigned SearchCondition.
     *
     * @internal
     *
     * @return SearchCondition
     */
    public function getSearchCondition(): SearchCondition;

    /**
     * Returns the configured field to columns mapping.
     *
     * @internal
     *
     * @return array[] [field-name][mapping-name] => QueryField
     */
    public function getFieldsMapping(): array;
}
