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

namespace Rollerworks\Component\Search\Doctrine\Orm;

use Doctrine\Common\Collections\ArrayCollection;
use Rollerworks\Component\Search\Exception\BadMethodCallException;
use Rollerworks\Component\Search\Exception\UnknownFieldException;

/**
 * A Doctrine ORM ConditionGenerator generates an DQL/SQL WHERE-clause
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
     * Set the default entity mapping configuration, only for fields
     * configured *after* this method.
     *
     * Note: Calling this method after calling setField() will not affect
     * fields that were already configured. Which means you can use this
     * method to configure chunks of configuration.
     *
     * @param string $entity Entity name (FQCN)
     * @param string $alias  Table alias as used in the query "u" for `FROM Acme:Users AS u`
     *
     * @throws BadMethodCallException When the where-clause is already generated
     *
     * @return static
     */
    public function setDefaultEntity(string $entity, string $alias);

    /**
     * Set the search field to Entity mapping mapping configuration.
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
     * Note: Associations are automatically resolved, but can only work for a single
     * property reference. If resolving is not possible the property must be owned by
     * the entity (not reference another entity).
     *
     * If the entity field is used in a many-to-many relation you must to reference the
     * targetEntity that is set on the ManyToMany mapping and use the entity field of
     * that entity.
     *
     * @param string $fieldName Name of the search field as registered in the FieldSet or
     *                          `field-name#mapping-name` to configure a secondary mapping
     * @param string $property  Entity field name
     * @param string $alias     Table alias as used in the query "u" for `FROM Acme:Users AS u`
     * @param string $entity    Entity name (FQCN or Doctrine aliased)
     * @param string $dbType    Doctrine DBAL supported type, eg. string (not text)
     *
     * @throws UnknownFieldException  When the field is not registered in the fieldset
     * @throws BadMethodCallException When the where-clause is already generated
     *
     * @return static
     */
    public function setField(string $fieldName, string $property, string $alias = null, string $entity = null, string $dbType = null);

    /**
     * Returns the generated where-clause.
     *
     * The Where-clause is wrapped inside a group so it can be safely used
     * with other conditions.
     *
     * @param string $prependQuery Prepend before the generated WHERE clause
     *                             Eg. " WHERE " or " AND ", ignored when WHERE
     *                             clause is empty.
     */
    public function getWhereClause(string $prependQuery = ''): string;

    /**
     * Updates the configured query object with the where-clause.
     *
     * @param string $prependQuery Prepend before the generated WHERE clause
     *                             Eg. " WHERE " or " AND ", ignored when WHERE
     *                             clause is empty. Default is ' WHERE '
     */
    public function updateQuery(string $prependQuery = ' WHERE ');

    public function getParameters(): ArrayCollection;
}
