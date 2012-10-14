<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Doctrine\Sql;

use Rollerworks\Bundle\RecordFilterBundle\Formatter\FormatterInterface;
use Rollerworks\Bundle\RecordFilterBundle\Value\FilterValuesBag;
use Rollerworks\Bundle\RecordFilterBundle\Value\SingleValue;
use Rollerworks\Bundle\RecordFilterBundle\FieldSet;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Metadata\MetadataFactoryInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query as OrmQuery;
use Doctrine\DBAL\Types\Type as ORMType;

/**
 * RecordFilter Doctrine ORM Where Builder.
 *
 * This class provides the functionality for creating an SQL WHERE-clause
 * based on the RecordFilter fieldSet.
 *
 * Keep the following in mind when using conversions.
 *
 *  * When using the result in DQL, custom functions must be registered in the ORM Configuration.
 *  * Conversion functions are per field and must be stateless, they get the type and connection
 *    information for performing operations.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class WhereBuilder
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

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
    protected $fieldsMappingCache = array();

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
     * @param MetadataFactoryInterface $metadataFactory
     * @param ContainerInterface       $container
     * @param EntityManager            $entityManager
     */
    public function __construct(MetadataFactoryInterface $metadataFactory, ContainerInterface $container, EntityManager $entityManager = null)
    {
        $this->metadataFactory = $metadataFactory;
        $this->entityManager = $entityManager;
        $this->container = $container;
    }

    /**
     * @param EntityManager $entityManager
     */
    public function setEntityManager(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
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
     * @param FormatterInterface $formatter
     * @param array              $entityAliasMapping is array with the Entity-class to 'in-query alias' mapping as alias => class
     * @param OrmQuery|null      $query              DQL query object (ony when using DQL)
     *
     * @return null|string
     */
    public function getWhereClause(FormatterInterface $formatter, array $entityAliasMapping = array(), OrmQuery $query = null)
    {
        // Use alias => class mapping instead of class => alias, because an class can be used by more then one alias.
        // More specific when using an INNER JOIN

        // Convert namespace aliases to the correct className
        if (!empty($entityAliasMapping)) {
            foreach ($entityAliasMapping as $alias => $entity) {
                if (false !== strpos($entity, ':')) {
                    $entityAliasMapping[$alias] = $this->entityManager->getClassMetadata($entity)->name;
                }
            }
        }

        if ($query) {
            $query->setHint('where_builder_conversions', $this);
            $this->isDql = true;
        } else {
            $this->isDql = false;
        }

        $this->entityAliases       = $entityAliasMapping;
        $this->fieldSet            = $formatter->getFieldSet();
        $this->fieldsMappingCache = array();

        return $this->buildWhere($formatter);
    }

    // TODO ADD CALLBACKS FOR SQL TreeWalker

    /**
     * @internal
     *
     * @param string $fieldName
     *
     * @return string
     */
    public function getFieldConversionSql($fieldName)
    {
        $field = $this->fieldSet->get($fieldName);
        $type = $this->entityManager->getClassMetadata($field->getPropertyRefClass())->getTypeOfField($field->getPropertyRefField());

        // Documentation claims its an object while in fact its an string
        if (!is_object($type)) {
            $type = ORMType::getType($type);
        }

        //$this->fieldsMappingCache[$fieldName]
        return $this->sqlFieldConversions[$fieldName]->convertField(
            $this->fieldsMappingCache[$fieldName],
            $type,
            $this->entityManager->getConnection()
        );
    }

    /**
     * Builds and returns the WHERE clause.
     *
     * @param FormatterInterface $formatter
     *
     * @return string|null
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

        return '' === $query ? null : $query;
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
     * Returns the correct column (with SQLField conversions applied).
     *
     * @param string $fieldName
     *
     * @return string
     *
     * @throws \InvalidArgumentException When the field can not be found in the fieldSet
     * @throws \LogicException           When there is no Property Reference and DQL is used
     */
    protected function getFieldColumn($fieldName)
    {
        if (isset($this->fieldsMappingCache[$fieldName])) {
            return $this->fieldsMappingCache[$fieldName];
        }

        if (!$this->fieldSet->has($fieldName)) {
            throw new \InvalidArgumentException(sprintf('Unable to get column. Field "%s" is not in fieldSet "%s".', $fieldName, $this->fieldSet->getSetName()));
        }
        $field = $this->fieldSet->get($fieldName);

        if (null === $field->getPropertyRefClass()) {
            if ($this->isDql) {
               throw new \LogicException(sprintf('Missing Property Reference for field "%s" in FieldSet "%s", this is required when using DQL.', $fieldName, $this->fieldSet->getSetName()));
            }
            $this->fieldsMappingCache[$fieldName] = $fieldName;

            return $fieldName;
        }

        $columnPrefix = '';
        $metadata = $this->entityManager->getClassMetadata($field->getPropertyRefClass());

        if (isset($this->entityAliases[$metadata->getTableName()])) {
            $columnPrefix = $this->entityAliases[$metadata->getTableName()] . '.';
        }

        if ($this->isDql) {
            $this->fieldsMappingCache[$fieldName] = $columnPrefix . $field->getPropertyRefField();
        } else {
            $this->fieldsMappingCache[$fieldName] = $columnPrefix . $metadata->getColumnName($field->getPropertyRefField());
        }

        if (isset($this->sqlFieldConversions[$fieldName]) && null !== $field->getPropertyRefClass()) {
            if ($this->isDql) {
                $this->fieldsMappingCache[$fieldName] = 'FILTER_VALUE_CONVERSION(' . var_export($fieldName) . ')';
            } else {
                $this->fieldsMappingCache[$fieldName] = $this->getFieldConversionSql($fieldName);
            }
        }

        return $this->fieldsMappingCache[$fieldName];
    }

    /**
     * Get an single value string.
     *
     * @param string $value
     * @param string $fieldName
     *
     * @return string|float|integer
     *
     * @throws \UnexpectedValueException When the returned value is not scalar
     *
     * FIXME PROVIDE FIELD OBJECT DIRECTLY TO LOWER CALL USAGE
     */
    protected function getValStr($value, $fieldName)
    {
        $field = $this->fieldSet->get($fieldName);

        if (null === $field->getPropertyRefClass()) {
            if (!is_scalar($value)) {
                throw new \UnexpectedValueException(sprintf('Value-type "%s" for field "%s" is not scalar.', (is_object($value) ? '(Object) ' .  get_class($value) : gettype($value)), $fieldName));
            }

            return $this->entityManager->getConnection()->quote($value);
        }

        $databasePlatform = $this->entityManager->getConnection()->getDatabasePlatform();
        $type = $this->entityManager->getClassMetadata($field->getPropertyRefClass())->getTypeOfField($field->getPropertyRefField());

        // Documentation claims its an object while in fact its an string
        if (!is_object($type)) {
            $type = ORMType::getType($type);
        }

        if (!isset($this->sqlValueConversions[$fieldName])) {
            // Set to null be default so we can skip this check the next round
            $this->sqlValueConversions[$fieldName] = null;

            if (null !== $field->getPropertyRefClass() && null !== $classMetadata = $this->metadataFactory->getMetadataForClass($field->getPropertyRefClass())) {
                $propertyName = $field->getPropertyRefField();

                if (isset($classMetadata->propertyMetadata[$propertyName]) && $classMetadata->propertyMetadata[$propertyName]->hasSqlConversion()) {
                    $this->sqlValueConversions[$fieldName] = $this->container->get($classMetadata->propertyMetadata[$propertyName]->getSqlConversionService());
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

            $value = $this->sqlValueConversions[$fieldName]->convertValue($value, $type, $this->entityManager->getConnection(), $this->isDql, $classMetadata->propertyMetadata[$propertyName]->getSqlConversionParams());
        }

        if (!is_scalar($value)) {
            throw new \UnexpectedValueException(sprintf('Final value-type "%s" for field "%s" is not scalar.', (is_object($value) ? '(Object) ' .  get_class($value) : gettype($value)), $fieldName));
        }

        return $value;
    }
}
