<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Doctrine\Dbal;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type as MappingType;
use Rollerworks\Component\Search\Doctrine\Dbal\Query\QueryField;
use Rollerworks\Component\Search\Doctrine\Dbal\Query\QueryGenerator;
use Rollerworks\Component\Search\Exception\BadMethodCallException;
use Rollerworks\Component\Search\Exception\UnknownFieldException;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\SearchConditionInterface;

/**
 * SearchCondition Doctrine DBAL WhereBuilder.
 *
 * This class provides the functionality for creating an SQL WHERE-clause
 * based on the provided SearchCondition.
 *
 * Keep the following in mind when using conversions.
 *
 *  * Conversions are performed per field and must be stateless,
 *    they receive the type and connection information for the conversion process.
 *  * Conversions apply at the SQL level, meaning they must be platform specific.
 *  * SQL conversions must be properly escaped to prevent SQL injections.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class WhereBuilder implements WhereBuilderInterface
{
    /**
     * @var SearchConditionInterface
     */
    private $searchCondition;

    /**
     * @var FieldSet
     */
    private $fieldset;

    /**
     * @var ValueConversionInterface[]|SqlValueConversionInterface[]|ConversionStrategyInterface[]
     */
    private $valueConversions = [];

    /**
     * @var SqlFieldConversionInterface[]
     */
    private $fieldConversions = [];

    /**
     * @var string
     */
    private $whereClause;

    /**
     * @var array[]
     */
    private $fields = [];

    /**
     * @var array[]
     */
    private $combinedFields = [];

    /**
     * @var Connection
     */
    private $connection;

    /**
     * Constructor.
     *
     * @param Connection               $connection      Doctrine DBAL Connection object
     * @param SearchConditionInterface $searchCondition SearchCondition object
     *
     * @throws BadMethodCallException When SearchCondition contains errors
     */
    public function __construct(Connection $connection, SearchConditionInterface $searchCondition)
    {
        if ($searchCondition->getValuesGroup()->hasErrors(true)) {
            throw new BadMethodCallException(
                'Unable to generate the where-clause with a SearchCondition that contains errors.'
            );
        }

        $this->searchCondition = $searchCondition;
        $this->fieldset = $searchCondition->getFieldSet();
        $this->connection = $connection;
    }

    /**
     * Set Field configuration for the query-generation.
     *
     * @param string             $fieldName Name of the Search-field
     * @param string             $column    DB column-name
     * @param string|MappingType $type      DB mapping-type
     * @param string             $alias     alias to use with the column
     *
     * @throws UnknownFieldException  When the field is not registered in the fieldset
     * @throws BadMethodCallException When the where-clause is already generated
     *
     * @return self
     */
    public function setField($fieldName, $column, $type = 'string', $alias = null)
    {
        if ($this->whereClause) {
            throw new BadMethodCallException(
                'WhereBuilder configuration methods cannot be accessed anymore once the where-clause is generated.'
            );
        }

        $this->fields[$fieldName] = [];
        $this->fields[$fieldName]['field'] = $this->searchCondition->getFieldSet()->get($fieldName);
        $this->fields[$fieldName]['db_type'] = is_object($type) ? $type : MappingType::getType($type);
        $this->fields[$fieldName]['alias'] = $alias;
        $this->fields[$fieldName]['column'] = $column;
    }

    /**
     * Set a CombinedField configuration for the query-generation.
     *
     * The $mappings expects an array with one or more mappings or null to unset this field.
     * Each mapping must have a `column`, all other keys are optional.
     *
     * @param string     $fieldName Name of the Search-field
     * @param array|null $mappings  ['mapping-name' => ['column' => '...', 'type' => 'string', 'alias' => null], ...]
     *
     * @throws UnknownFieldException  When the field is not registered in the fieldset
     * @throws BadMethodCallException When the where-clause is already generated
     *
     * @return self
     */
    public function setCombinedField($fieldName, array $mappings = null)
    {
        if ($this->whereClause) {
            throw new BadMethodCallException(
                'WhereBuilder configuration methods cannot be accessed anymore once the where-clause is generated.'
            );
        }

        $fieldConfig = $this->fieldset->get($fieldName);

        if (null === $mappings) {
            unset($this->combinedFields[$fieldName]);

            return $this;
        }

        foreach ($mappings as $n => $mapping) {
            if (!isset($mapping['column'])) {
                throw new \InvalidArgumentException(
                    sprintf('Combined search field "%s" is missing "column" at index "%s".', $fieldName, $n)
                );
            }

            if (!isset($mapping['type'])) {
                $mapping['type'] = 'string';
            }

            $this->combinedFields[$fieldName][$n] = [];
            $this->combinedFields[$fieldName][$n]['field'] = $fieldConfig;
            $this->combinedFields[$fieldName][$n]['alias'] = isset($mapping['alias']) ? $mapping['alias'] : null;
            $this->combinedFields[$fieldName][$n]['column'] = $mapping['column'];
            $this->combinedFields[$fieldName][$n]['db_type'] = is_object($mapping['type']) ? $mapping['type'] : MappingType::getType($mapping['type']);
        }

        unset($this->fields[$fieldName]);

        return $this;
    }

    /**
     * Set the converters for a field.
     *
     * Setting is done per type (field or value), any existing conversions are overwritten.
     *
     * @param string                                               $fieldName
     * @param ValueConversionInterface|SqlFieldConversionInterface $converter
     *
     * @throws UnknownFieldException  When the field is not registered in the fieldset
     * @throws BadMethodCallException When the where-clause is already generated
     *
     * @return self
     */
    public function setConverter($fieldName, $converter)
    {
        if ($this->whereClause) {
            throw new BadMethodCallException('WhereBuilder configuration methods cannot be accessed anymore once the where-clause is generated.');
        }

        if (!$this->searchCondition->getFieldSet()->has($fieldName)) {
            throw new UnknownFieldException($fieldName);
        }

        if ($converter instanceof ValueConversionInterface) {
            $this->valueConversions[$fieldName] = $converter;
        }

        if ($converter instanceof SqlFieldConversionInterface) {
            $this->fieldConversions[$fieldName] = $converter;
        }

        return $this;
    }

    /**
     * Returns the generated where-clause.
     *
     * The Where-clause is wrapped inside a group so it
     * can be safely used with other conditions.
     *
     * Values are embedded with in the Query.
     *
     * @param string $prependQuery Prepends this string to the where-clause
     *                             (" WHERE " or " AND " for example)
     *
     * @return string
     */
    public function getWhereClause($prependQuery = '')
    {
        if (null === $this->whereClause) {
            $fields = $this->processFields();

            $queryGenerator = new QueryGenerator(
                $this->connection, $this->getQueryPlatform($fields), $fields
            );

            $this->whereClause = $queryGenerator->getGroupQuery(
                $this->searchCondition->getValuesGroup()
            );
        }

        if ('' !== $this->whereClause) {
            return $prependQuery.$this->whereClause;
        }

        return '';
    }

    /**
     * @return SearchConditionInterface
     */
    public function getSearchCondition()
    {
        return $this->searchCondition;
    }

    /**
     * @return ConversionStrategyInterface[]|SqlValueConversionInterface[]|ValueConversionInterface[]
     */
    public function getValueConversions()
    {
        return $this->valueConversions;
    }

    /**
     * @return SqlFieldConversionInterface[]|ConversionStrategyInterface[]
     */
    public function getFieldConversions()
    {
        return $this->fieldConversions;
    }

    private function processFields()
    {
        $fields = [];

        foreach ($this->fields as $fieldName => $field) {
            $fields[$fieldName] = new QueryField(
                $this->searchCondition->getFieldSet()->get($fieldName),
                $field['db_type'],
                $field['alias'],
                $field['column'],
                isset($this->fieldConversions[$fieldName]) ? $this->fieldConversions[$fieldName] : null,
                isset($this->valueConversions[$fieldName]) ? $this->valueConversions[$fieldName] : null
            );
        }

        foreach ($this->combinedFields as $fieldName => $subFieldsConfig) {
            $subsFieldNames = [];

            foreach ($subFieldsConfig as $n => $field) {
                $fieldNameN = $fieldName.'#'.$n;

                $subsFieldNames[] = $fieldNameN;
                $fields[$fieldNameN] = new QueryField(
                    $field['field'],
                    $field['db_type'],
                    $field['alias'],
                    $field['column'],
                    isset($this->fieldConversions[$fieldName]) ? $this->fieldConversions[$fieldName] : null,
                    isset($this->valueConversions[$fieldName]) ? $this->valueConversions[$fieldName] : null
                );
            }

            $fields[$fieldName] = $subsFieldNames;
        }

        return $fields;
    }

    private function getQueryPlatform(array $fields)
    {
        $dbPlatform = ucfirst($this->connection->getDatabasePlatform()->getName());
        $platformClass = 'Rollerworks\\Component\\Search\\Doctrine\\Dbal\\QueryPlatform\\'.$dbPlatform.'QueryPlatform';

        if (class_exists($platformClass)) {
            return new $platformClass($this->connection, $fields);
        }

        throw new \RuntimeException(sprintf('No supported class found for database-platform "%s".', $dbPlatform));
    }
}
