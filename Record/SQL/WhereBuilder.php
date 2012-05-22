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
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Metadata\MetadataFactoryInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\Types\Type as ORMType;

/**
 * SQL RecordFilter Where Builder.
 *
 * This class provides the functionality for creating an SQL WHERE-clause based on the RecordFilter fieldSet.
 *
 * Keep the following in mind when using conversions.
 * * When using the result in DQL, custom functions must be registered in the ORM Configuration.
 * * Conversion functions are per field and must be stateless, they get the type and connection information for performing operations.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class WhereBuilder
{
    /**
     * @var EntityManager
     */
    protected $entityManager = null;

    /**
     * @var MetadataFactoryInterface
     */
    protected $metadataFactory;

    /**
     * @var ContainerInterface
     */
    protected $container;

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
     * Constructor.
     *
     * @param EntityManager            $entityManager
     * @param MetadataFactoryInterface $metadataFactory
     */
    public function __construct(EntityManager $entityManager, MetadataFactoryInterface $metadataFactory)
    {
        $this->entityManager   = $entityManager;
        $this->metadataFactory = $metadataFactory;
    }

    /**
     * Set the DIC container for SQL value-conversions.
     *
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Set the SQL conversion configuration for an field.
     *
     * Only converter per field, existing one is overwritten.
     *
     * @param string                      $fieldName
     * @param SqlFieldConversionInterface $conversionObj
     */
    public function setConversionForField($fieldName, SqlFieldConversionInterface $conversionObj)
    {
        $this->sqlFieldConversions[$fieldName] = $conversionObj;
    }

    /**
     * Returns the WHERE clause for the query.
     *
     * @param FieldSet           $fieldSet
     * @param FormatterInterface $formatter
     * @param array              $entityAliases Array with the Entity-class to 'in-query alias' mapping as alias => class
     * @param boolean            $isDql
     *
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
        $this->entityAliases       = $entityAliases;
        $this->fieldSet            = $fieldSet;
        $this->isDql               = $isDql;

        return $this->buildWhere($formatter);
    }

    /**
     * Returns the correct column (with SQLField conversions applied).
     *
     * @param string $fieldName
     *
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

        $columnPrefix = '';
        $metadata = $this->entityManager->getClassMetadata($field->getEntityClass());

        if (isset($this->entityAliases[$metadata->getTableName()])) {
            $columnPrefix = $this->entityAliases[$metadata->getTableName()] . '.';
        }

        $this->columnsMappingCache[$fieldName] = $columnPrefix . $metadata->getColumnName($field->getEntityField());

        if (isset($this->sqlFieldConversions[$fieldName]) && null !== $field->getEntityClass()) {
            $type = $this->entityManager->getClassMetadata($field->getEntityClass())->getTypeOfField($field->getEntityField());

            // Documentation claims its an object while in fact its an string
            if (!is_object($type)) {
                $type = ORMType::getType($type);
            }

            $this->columnsMappingCache[$fieldName] = $this->sqlFieldConversions[$fieldName]->convertField(
                $this->columnsMappingCache[$fieldName],
                $type,
                $this->entityManager->getConnection(),
                $this->isDql
            );
        }

        return $this->columnsMappingCache[$fieldName];
    }

    /**
     * Returns an comma-separated list of values.
     *
     * @param SingleValue[] $values
     * @param string        $fieldName
     *
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
     *
     * @return string
     */
    protected function buildWhere(FormatterInterface $formatter)
    {
        $query = '';

        foreach ($formatter->getFilters() as $filters) {
            $query .= "(\n";

            /** @var FilterValuesBag $valuesBag */
            foreach ($filters as $fieldName => $valuesBag) {
                if (!$this->fieldSet->has($fieldName)) {
                    continue;
                }

                $column = $this->getFieldColumn($fieldName);

                if ($valuesBag->hasSingleValues()) {
                    $query .= sprintf('%s IN(%s) AND ', $column, $this->createInList($valuesBag->getSingleValues(), $fieldName));
                }

                if ($valuesBag->hasExcludes()) {
                    $query .= sprintf('%s NOT IN(%s) AND ', $column, $this->createInList($valuesBag->getExcludes(), $fieldName));
                }

                foreach ($valuesBag->getRanges() as $range) {
                    $query .= sprintf('(%s BETWEEN %s AND %s) AND ', $column, $this->getValStr($range->getLower(), $fieldName), $this->getValStr($range->getUpper(), $fieldName));
                }

                foreach ($valuesBag->getExcludedRanges() as $range) {
                    $query .= sprintf('(%s NOT BETWEEN %s AND %s) AND ', $column, $this->getValStr($range->getLower(), $fieldName), $this->getValStr($range->getUpper(), $fieldName));
                }

                foreach ($valuesBag->getCompares() as $comp) {
                    $query .= sprintf('%s %s %s AND ', $column, $comp->getOperator(), $this->getValStr($comp->getValue(), $fieldName));
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
     *
     * @return string|float|integer
     *
     * @throws \RuntimeException
     * @throws \UnexpectedValueException When the returned value is not scalar
     */
    protected function getValStr($value, $fieldName)
    {
        if (null === $this->fieldSet) {
            throw new \RuntimeException('This method should be called after a fieldSet is set.');
        }

        $field = $this->fieldSet->get($fieldName);

        if (null === $field->getEntityClass()) {
            return $this->entityManager->getConnection()->quote($value);
        }

        $databasePlatform = $this->entityManager->getConnection()->getDatabasePlatform();
        $type = $this->entityManager->getClassMetadata($field->getEntityClass())->getTypeOfField($field->getEntityField());

        // Documentation claims its an object while in fact its an string
        if (!is_object($type)) {
            $type = ORMType::getType($type);
        }

        if (!isset($this->sqlValueConversions[$fieldName])) {
            // Set to null be default so we can skip this check the next round
            $this->sqlValueConversions[$fieldName] = null;

            if (null !== $field->getEntityClass() && null !== $classMetadata = $this->metadataFactory->getMetadataForClass($field->getEntityClass())) {
                $propertyName = $field->getEntityField();

                if (isset($classMetadata->propertyMetadata[$propertyName]) && $classMetadata->propertyMetadata[$propertyName]->hasSqlConversion()) {
                    $class = $classMetadata->propertyMetadata[$propertyName]->getSqlConversionClass();
                    $this->sqlValueConversions[$fieldName] = new $class($classMetadata->propertyMetadata[$propertyName]->getSqlConversionParams());

                    if ($this->sqlValueConversions[$fieldName] instanceof ContainerAwareInterface) {
                        $this->sqlValueConversions[$fieldName]->setContainer($this->container);
                    }
                }
            }
        }

        if (null === $this->sqlValueConversions[$fieldName]) {
            $value = $type->convertToDatabaseValue($value, $databasePlatform);

            // String values must be quoted.
            if (\PDO::PARAM_STR === $type->getBindingType() || \PDO::PARAM_LOB === $type->getBindingType()) {
                $value = $this->entityManager->getConnection()->quote($value);
            }
        } else {
            if ($this->sqlValueConversions[$fieldName]->requiresBaseConversion()) {
                $value = $type->convertToDatabaseValue($value, $databasePlatform);
            }

            $value = $this->sqlValueConversions[$fieldName]->convertValue($value, $type, $this->entityManager->getConnection(), $this->isDql);
        }

        if (!is_scalar($value)) {
            throw new \UnexpectedValueException(sprintf('Final value-type "%s" for field "%s" is not scalar.', (is_object($value) ? '(Object) ' .  get_class($value) : gettype($value)), $fieldName));
        }

        return $value;
    }
}
