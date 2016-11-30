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

use Doctrine\DBAL\Types\Type as MappingType;
use Rollerworks\Component\Search\Doctrine\Dbal\SqlFieldConversionInterface;
use Rollerworks\Component\Search\Doctrine\Dbal\ValueConversionInterface;
use Rollerworks\Component\Search\Exception\UnknownFieldException;

/**
 * The ConfigurableWhereBuilderInterface allows to configure a WhereBuilder's
 * mapping-data and set conversions.
 */
interface ConfigurableWhereBuilderInterface extends WhereBuilderInterface
{
    /**
     * Set the entity mapping per class.
     *
     * @param string $entityName class or Doctrine alias
     * @param string $alias      Entity alias as used in the query.
     *                           Set to the null to remove the mapping
     */
    public function setEntityMapping($entityName, $alias);

    /**
     * Set the entity mappings.
     *
     * Mapping is set as [class] => in-query-entity-alias.
     *
     * Caution. This will overwrite any configured entity-mappings.
     *
     * @param array $mapping
     *
     * @return self
     */
    public function setEntityMappings(array $mapping);

    /**
     * Set Field configuration for the query-generation.
     *
     * Note: The property must be owned by the entity (not reference another entity).
     * If the entity field is used in a many-to-many relation you must to reference the
     * targetEntity that is set on the ManyToMany mapping and use the entity field of that entity.
     *
     * @param string             $fieldName   Name of the Search field
     * @param string             $alias       Entity alias as used in the query
     * @param string             $entity      Entity name (FQCN or Doctrine aliased)
     * @param string             $property    Entity field name
     * @param string|MappingType $mappingType Doctrine Mapping-type
     *
     * @throws UnknownFieldException When the field is not registered in the fieldset
     *
     * @return self
     */
    public function setField($fieldName, $alias, $entity = null, $property = null, $mappingType = null);

    /**
     * Set a CombinedField configuration for the query-generation.
     *
     * The $mappings expects an array with one or more mappings.
     * Each mapping must have a `property`, all other keys are optional.
     *
     * @param string     $fieldName Name of the Search-field
     * @param array|null $mappings  ['mapping-name' => ['property' => '...', 'class' => '...', 'type' => 'string',
     *                              'alias' => null], ...]
     *
     * @throws UnknownFieldException When the field is not registered in the fieldset
     *
     * @return self
     */
    public function setCombinedField($fieldName, array $mappings);

    /**
     * Set the converters for a field.
     *
     * Setting is done per type (field or value), any existing conversions are overwritten.
     *
     * @param string                                               $fieldName
     * @param ValueConversionInterface|SqlFieldConversionInterface $converter
     *
     * @throws UnknownFieldException When the field is not registered in the fieldset
     *
     * @return self
     */
    public function setConverter($fieldName, $converter);
}
