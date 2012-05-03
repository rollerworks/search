<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\RecordFilterBundle\Record\Sql;

use Rollerworks\RecordFilterBundle\Formatter\FormatterInterface;
use Rollerworks\RecordFilterBundle\Value\FilterValuesBag;
use Rollerworks\RecordFilterBundle\FieldSet;
use Rollerworks\RecordFilterBundle\Value\SingleValue;
use Doctrine\ORM\EntityManager;

/**
 * SQL RecordFilter Where Builder.
 *
 * This class provides the functionality for creating an SQL WHERE-clause based on the RecordFilter fieldSet.
 *
 * Keep the following in mind when using conversions.
 * * When using the result in DQL, custom functions must be registered in the ORM Configuration.
 * * Conversion functions must be stateless, they get the type and connection for information and performing operations.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class WhereBuilder
{
    /**
     * Doctrine EntityManager
     *
     * @var EntityManager
     */
    protected $entityManager = null;

    /**
     * @var FieldSet
     */
    protected $fieldSet;

    /**
     * @var array
     */
    protected $entityAliases = array();

    /**
     * @var array
     */
    protected $columnsMappingCache = array();

    /**
     * @var SqlValueConversionInterface[]
     */
    protected $sqlValueConversions = array();

    /**
     * @var SqlFieldConversionInterface[]
     */
    protected $sqlFieldConversions = array();

    /**
     * @var boolean
     */
    protected $isDql = false;

    /**
     * Constructor
     *
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Set the SQL conversion configuration for an value.
     *
     * The configuration applies to all FieldSet's.
     *
     * @param string                      $fieldName
     * @param SqlValueConversionInterface $conversionObj
     */
    public function setSqlConversionForValue($fieldName, SqlValueConversionInterface $conversionObj)
    {
        $this->sqlValueConversions[$fieldName] = $conversionObj;
    }

    /**
     * Set the SQL conversion configuration for an SQL-field.
     *
     * The configuration applies to all FieldSet's.
     *
     * @param string                      $fieldName
     * @param SqlFieldConversionInterface $conversionObj
     */
    public function setSqlConversionForField($fieldName, SqlFieldConversionInterface $conversionObj)
    {
        $this->sqlFieldConversions[$fieldName] = $conversionObj;
    }

    /**
     * Returns the WHERE clause for the query.
     *
     * @param FieldSet           $fieldSet
     * @param FormatterInterface $formatter
     * @param array              $entityAliases Array with the Entity-class to 'in-query alias' mapping as alias => class
     * @param boolean            $isDql         Is the WHERE case to be used inside DQL?
     * @return null|string
     */
    public function getWhereClause(FieldSet $fieldSet, FormatterInterface $formatter, array $entityAliases = array(), $isDql = false)
    {
        // Use alias => class mapping instead of class => alias, because an class can be used by more then one alias.
        // More specific when using an INNER JOIN

        // Convert namespace aliases to the correct className
        if (!empty($entityAliases)) {
            foreach ($entityAliases as $alias => $entity ) {
                if (false !== strpos($entity, ':')) {
                    $entityAliases[$alias] = $this->entityManager->getClassMetadata($entity)->name;
                }
            }
        }

        $this->columnsMappingCache = array();
        $this->entityAliases = $entityAliases;
        $this->fieldSet = $fieldSet;
        $this->isDql = $isDql;

        return $this->buildWhere($formatter);
    }

    /**
     * Returns the correct column name.
     *
     * @param string $fieldName
     * @return string
     *
     * @throws \InvalidArgumentException When the field can not be found in the fieldSet
     */
    protected function getFieldColumn($fieldName)
    {
        if (isset($this->columnsMappingCache[$fieldName])) {
            return $this->columnsMappingCache[$fieldName];
        }

        if (!$this->fieldSet->has($fieldName)) {
            throw new \InvalidArgumentException(sprintf('Unable to get column. Field "%s" is not in fieldSet "%s".', $fieldName, $this->fieldSet->getSetName()));
        }

        $field = $this->fieldSet->get($fieldName);
        if (null === $field->getEntityClass()) {
            $this->columnsMappingCache[$fieldName] = $fieldName;

            return $fieldName;
        }

        $metadata = $this->entityManager->getClassMetadata($field->getEntityClass());
        $columnPrefix = '';

        if (isset($this->entityAliases[$metadata->getTableName()])) {
            $columnPrefix = $this->entityAliases[$metadata->getTableName()] . '.';
        }
        $this->columnsMappingCache[$fieldName] = $columnPrefix . $metadata->getColumnName($field->getEntityField());

        return $this->columnsMappingCache[$fieldName];
    }

    /**
     * Returns an comma-separated list of values.
     *
     * @param SingleValue[] $values
     * @param string        $fieldName
     * @return string
     */
    protected function createInList($values, $fieldName)
    {
        $inList = '';
        foreach ($values as $value) {
            $inList .= $this->getValStr($value->getValue(), $fieldName) . ', ';
        }

        return trim($inList, ', ');
    }

    /**
     * Builds and returns the WHERE clause
     *
     * @param FormatterInterface $formatter
     * @return string
     */
    protected function buildWhere(FormatterInterface $formatter)
    {
        $query = '';

        foreach ($formatter->getFilters() as $filters) {
            $query .= "(\n";

            /** @var \Rollerworks\RecordFilterBundle\Value\FilterValuesBag $valuesBag */
            foreach ($filters as $fieldName => $valuesBag) {
                if (!$this->fieldSet->has($fieldName)) {
                    continue;
                }

                $columnName = $this->getFieldColumn($fieldName);

                $field = $this->fieldSet->get($fieldName);
                if (isset($this->sqlFieldConversions[$fieldName]) && null !== $field->getEntityClass()) {
                    $columnName = $this->sqlFieldConversions[$fieldName]->convertField(
                        $columnName,
                        $this->entityManager->getClassMetadata($field->getEntityClass())->getTypeOfField($field->getEntityField()),
                        $this->entityManager->getConnection(),
                        $this->isDql);
                }

                if($valuesBag->hasSingleValues()) {
                    $query .= sprintf('%s IN(%s) AND ', $columnName, $this->createInList($valuesBag->getSingleValues(), $fieldName));
                }

                if($valuesBag->hasExcludes()) {
                    $query .= sprintf('%s NOT IN(%s) AND ', $columnName, $this->createInList($valuesBag->getExcludes(), $fieldName));
                }

                foreach ($valuesBag->getRanges() as $range) {
                    $query .= sprintf('%s BETWEEN %s AND %s AND ', $columnName, $this->getValStr($range->getLower(), $fieldName), $this->getValStr($range->getUpper(), $fieldName));
                }

                foreach ($valuesBag->getExcludedRanges() as $range) {
                    $query .= sprintf('%s NOT BETWEEN %s AND %s AND ', $columnName, $this->getValStr($range->getLower(), $fieldName), $this->getValStr($range->getUpper(), $fieldName));
                }

                foreach ($valuesBag->getCompares() as $comp) {
                    $query .= sprintf('%s %s %s AND ', $columnName, $comp->getOperator(), $this->getValStr($comp->getValue(), $fieldName));
                }
            }
            $query = trim($query, " AND ") . ")\n OR ";
        }
        $query = trim($query, " OR ");

        return $query;
    }

    /**
     * Get an single value string.
     *
     * @param string $value
     * @param string $fieldName
     * @return mixed
     *
     * @throws \UnderflowException
     * @throws \UnexpectedValueException When the returned value is not scalar
     */
    protected function getValStr($value, $fieldName)
    {
        if (null === $this->fieldSet) {
            throw new \UnderflowException('This method should be called after a fieldSet is set.');
        }

        $field = $this->fieldSet->get($fieldName);
        if (null === $field->getEntityClass()) {
            return $this->entityManager->getConnection()->quote($value);
        }

        $databasePlatform = $this->entityManager->getConnection()->getDatabasePlatform();
        $type = $this->entityManager->getClassMetadata($field->getEntityClass())->getTypeOfField($field->getEntityField());

        if ((isset($this->sqlValueConversions[$fieldName]) && $this->sqlValueConversions[$fieldName]->requiresBaseConversion()) || !isset($this->sqlValueConversions[$fieldName])) {
            $value = $type->convertToDatabaseValue($value, $databasePlatform);
        }

        if (isset($this->sqlValueConversions[$fieldName])) {
            $value = $this->sqlValueConversions[$fieldName]->convertValue($value, $type, $this->entityManager->getConnection(), $this->isDql);
        }
        // String values must be quoted.
        elseif (\PDO::PARAM_STR === $type->getBindingType() || \PDO::PARAM_LOB === $type->getBindingType()) {
            $value = $this->entityManager->getConnection()->quote($value);
        }

        if (!is_scalar($value)) {
            throw new \UnexpectedValueException(sprintf('Final value-type "%s" is not scalar.', (is_object($value) ? '(Object)' .  get_class($value) : gettype($value))));
        }

        return $value;
    }
}
