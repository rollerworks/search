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

use Doctrine\DBAL\Driver\Connection;
use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Types\Type as ORMType;
use Rollerworks\Component\Search\Exception\BadMethodCallException;
use Rollerworks\Component\Search\Exception\UnknownFieldException;
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
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class WhereBuilder extends AbstractWhereBuilder implements WhereBuilderInterface
{
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
     * @throws BadMethodCallException When SearchCondition contains errors.
     */
    public function __construct(Connection $connection, SearchConditionInterface $searchCondition)
    {
        if ($searchCondition->getValuesGroup()->hasErrors()) {
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
     * @param string         $fieldName Name of the Search-field
     * @param string         $column    DB column-name
     * @param string|ORMType $type      DB-type string or object
     * @param string         $alias     alias to use with the column
     *
     * @return self
     *
     * @throws UnknownFieldException  When the field is not registered in the fieldset.
     * @throws BadMethodCallException When the where-clause is already generated.
     */
    public function setField($fieldName, $column, $type = 'string', $alias = null)
    {
        if ($this->whereClause) {
            throw new BadMethodCallException(
                'WhereBuilder configuration methods cannot be accessed anymore once the where-clause is generated.'
            );
        }

        if (!$this->searchCondition->getFieldSet()->has($fieldName)) {
            throw new UnknownFieldException($fieldName);
        }

        $this->fields[$fieldName] = array();
        $this->fields[$fieldName]['field']   = $this->searchCondition->getFieldSet()->get($fieldName);
        $this->fields[$fieldName]['db_type'] = is_object($type) ? $type : ORMType::getType($type);
        $this->fields[$fieldName]['column']  = $alias ? $alias.'.'.$column : $column;
    }

    /**
     * Returns the generated where-clause.
     *
     * The Where-clause is wrapped inside a group so it
     * can be safely used with other conditions.
     *
     * @param bool $embedValues Whether to embed the values, default is to assign as parameters
     *
     * @return string
     */
    public function getWhereClause($embedValues = false)
    {
        if (null !== $this->whereClause) {
            return $this->whereClause;
        }

        $this->processFields();

        $this->queryGenerator = new QueryGenerator(
            $this->connection,
            $this->searchCondition,
            $this->fields,
            $this->parameterPrefix,
            $embedValues
        );

        $this->whereClause = $this->queryGenerator->getGroupQuery(
            $this->searchCondition->getValuesGroup()
        );

        return $this->whereClause;
    }

    /**
     * {@inheritdoc}
     */
    public function bindParameters(Statement $statement)
    {
        if (!$this->queryGenerator) {
            throw new BadMethodCallException('No Parameters available, call getWhereClause() first.');
        }

        foreach ($this->queryGenerator->getParameters() as $paramName => $paramValue) {
            $statement->bindValue(
                $paramName,
                $paramValue,
                $this->queryGenerator->getParameterType($paramName)
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getParameterTypes()
    {
        if ($this->queryGenerator) {
            return $this->queryGenerator->getParameterTypes();
        }

        return array();
    }

    /**
     * Returns the parameters-type that where set during the generation process.
     *
     * @param string $name
     *
     * @return \Doctrine\DBAL\Types\Type|null
     */
    public function getParametersType($name)
    {
        if ($this->queryGenerator) {
            return $this->queryGenerator->getParameterType($name);
        }

        return;
    }

    private function processFields()
    {
        foreach (array_keys($this->fields) as $fieldName) {
            if (isset($this->fieldConversions[$fieldName])) {
                $this->fields[$fieldName]['field_convertor'] = $this->fieldConversions[$fieldName];
            } else {
                $this->fields[$fieldName]['field_convertor'] = null;
            }

            if (isset($this->valueConversions[$fieldName])) {
                $this->fields[$fieldName]['value_convertor'] = $this->valueConversions[$fieldName];
            } else {
                $this->fields[$fieldName]['value_convertor'] = null;
            }
        }
    }
}
