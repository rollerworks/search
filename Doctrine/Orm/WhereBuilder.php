<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Doctrine\Orm;

use Rollerworks\Bundle\RecordFilterBundle\Formatter\FormatterInterface;
use Rollerworks\Bundle\RecordFilterBundle\Value\FilterValuesBag;
use Rollerworks\Bundle\RecordFilterBundle\Value\SingleValue;
use Rollerworks\Bundle\RecordFilterBundle\FieldSet;
use Rollerworks\Bundle\RecordFilterBundle\FilterField;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Metadata\MetadataFactoryInterface;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\AbstractQuery as OrmQuery;
use Doctrine\DBAL\Types\Type as ORMType;
use Doctrine\ORM\Query as DqlQuery;
use Doctrine\ORM\EntityManager;

/**
 * RecordFilter Doctrine ORM Where Builder.
 *
 * This class provides the functionality for creating an SQL/DQL WHERE-clause
 * based on the RecordFilter fieldSet.
 *
 * Keep the following in mind when using conversions.
 *
 *  * Conversion functions are per field and must be stateless, they get the type and connection
 *    information for performing operations.
 *  * Unless using one DB platform, return the correct SQL for the used platform.
 *  * String values will always be quoted in the SQL result.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * @todo Allow setting an field conversion by metadata
 * @todo Value strategy, to use multiple "value-types" , each one uses an new IN(), null is default, -1 will be used as-is
 * @todo Cache value conversion registration in the Factory
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
     * Maps field to column (with alias) without conversion.
     *
     * @var array
     */
    protected $fieldsMappingCache = array();

    /**
     * Conversion result for fields.
     *
     * @var array
     */
    protected $fieldConversionCache = array();

    /**
     * @var SqlFieldConversionInterface[]
     */
    protected $sqlFieldConversions = array();

    /**
     * @var SqlValueConversionInterface[]|SqlValueAdvancedConversionInterface[]|array
     */
    protected $sqlValueConversions = array();

    /**
     * @var OrmQuery|null
     */
    protected $query;

    /**
     * @var AbstractPlatform
     */
    protected $databasePlatform;

    /**
     * @var array
     */
    private $paramPosition = array();

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
     * Overwrite the default Entity manager.
     *
     * @param EntityManager $entityManager
     */
    public function setEntityManager(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Set the SQL conversion configuration for an field.
     *
     * Only one converter per field, existing one is overwritten.
     *
     * @param string                      $fieldName
     * @param SqlFieldConversionInterface $conversionObj
     */
    public function setFieldConversion($fieldName, SqlFieldConversionInterface $conversionObj)
    {
        $this->sqlFieldConversions[$fieldName] = $conversionObj;
    }

    /**
     * Returns the WHERE clause for the query.
     *
     * WARNING: Don't set an $query object when using the result in union.
     * Calling this method resets the parameter index counter.
     *
     * @param FormatterInterface $formatter
     * @param array              $entityAliasMapping An array with the alias-mapping as [class or Bundle:Class] => entity-alias
     * @param OrmQuery|null      $query              ORM Query object (required for DQL).
     *
     * @return null|string
     *
     * @throws \InvalidArgumentException when alias-map is empty but $query is set
     */
    public function getWhereClause(FormatterInterface $formatter, array $entityAliasMapping = array(), OrmQuery $query = null)
    {
        // Convert namespace aliases to the correct className
        foreach ($entityAliasMapping as $entity => $alias ) {
            if (false !== strpos($entity, ':')) {
                $entityAliasMapping[$this->entityManager->getClassMetadata($entity)->name] = $alias;
            }
        }

        $this->query = $query;
        if ($query) {
            if (!$entityAliasMapping) {
                throw new \InvalidArgumentException('$entityAliasMapping must be set when using an query object.');
            }

            if ($query instanceof DqlQuery) {
                $query->setHint('where_builder_conversions', $this);
            }
        }

        $this->fieldSet = $formatter->getFieldSet();
        $this->entityAliases = $entityAliasMapping;
        $this->databasePlatform = $this->entityManager->getConnection()->getDatabasePlatform();

        $this->fieldsMappingCache   = array();
        $this->fieldConversionCache = array();
        $this->paramPosition        = array();

        return $this->buildWhere($formatter);
    }

    /**
     * Returns the SQL for the Field conversion.
     *
     * @internal
     *
     * @param string      $fieldName
     * @param string      $column
     * @param FilterField $field
     *
     * @return string
     */
    public function getFieldConversionSql($fieldName, $column, FilterField $field = null)
    {
        if (isset($this->fieldConversionCache[$fieldName])) {
            return $this->fieldConversionCache[$fieldName];
        }

        if (!$field) {
            $field = $this->fieldSet->get($fieldName);
        }

        $type = $this->entityManager->getClassMetadata($field->getPropertyRefClass())->getTypeOfField($field->getPropertyRefField());
        if (!is_object($type)) {
            $type = ORMType::getType($type);
        }

        $this->fieldConversionCache[$fieldName] = $this->sqlFieldConversions[$fieldName]->getConvertFieldSql(
            $column,
            $type,
            $this->entityManager->getConnection()
        );

        return $this->fieldConversionCache[$fieldName];
    }

    /**
     * Returns the SQL for the SQL wrapped-value conversion.
     *
     * @internal
     *
     * @param string      $fieldName
     * @param string      $value
     * @param FilterField $field
     * @param ORMType     $type
     *
     * @return string
     */
    public function getValueConversionSql($fieldName, $value, FilterField $field = null, ORMType $type = null)
    {
        if (!$field) {
            $field = $this->fieldSet->get($fieldName);
        }

        if (!$type) {
            $type = $this->entityManager->getClassMetadata($field->getPropertyRefClass())->getTypeOfField($field->getPropertyRefField());
            if (!is_object($type)) {
                $type = ORMType::getType($type);
            }
        }

        return $this->sqlValueConversions[$fieldName][0]->getConvertValuedSql(
            $value,
            $type,
            $this->entityManager->getConnection(),
            $this->sqlValueConversions[$fieldName][1]
        );
    }

    /**
     * Builds and returns the WHERE clause.
     *
     * Fields not having an PropertyReference are ignored.
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
                $field = $this->fieldSet->get($fieldName);

                if (null === $field->getPropertyRefClass()) {
                    continue;
                }

                $column = $this->getFieldColumn($fieldName, $field);

                if ($valuesBag->hasSingleValues()) {
                    $query .= sprintf('%s IN(%s) AND ', $column, $this->createInList($valuesBag->getSingleValues(), $fieldName, $field));
                }

                if ($valuesBag->hasExcludes()) {
                    $query .= sprintf('%s NOT IN(%s) AND ', $column, $this->createInList($valuesBag->getExcludes(), $fieldName, $field));
                }

                foreach ($valuesBag->getRanges() as $range) {
                    $query .= sprintf('(%s BETWEEN %s AND %s) AND ', $column, $this->getValStr($range->getLower(), $fieldName, $field), $this->getValStr($range->getUpper(), $fieldName, $field));
                }

                foreach ($valuesBag->getExcludedRanges() as $range) {
                    $query .= sprintf('(%s NOT BETWEEN %s AND %s) AND ', $column, $this->getValStr($range->getLower(), $fieldName, $field), $this->getValStr($range->getUpper(), $fieldName, $field));
                }

                foreach ($valuesBag->getCompares() as $comp) {
                    $query .= sprintf('%s %s %s AND ', $column, $comp->getOperator(), $this->getValStr($comp->getValue(), $fieldName, $field));
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
     * @param FilterField   $field
     *
     * @return string
     */
    protected function createInList($values, $fieldName, FilterField $field)
    {
        $inList = '';
        foreach ($values as $value) {
            $inList .= $this->getValStr($value->getValue(), $fieldName, $field) . ', ';
        }

        return trim($inList, ', ');
    }

    /**
     * Returns the correct column (with SQLField conversions applied).
     *
     * @param string      $fieldName
     * @param FilterField $field
     *
     * @return string
     */
    protected function getFieldColumn($fieldName, FilterField $field)
    {
        if (isset($this->fieldsMappingCache[$fieldName])) {
            return $this->fieldsMappingCache[$fieldName];
        }

        if (isset($this->entityAliases[$field->getPropertyRefClass()])) {
            $columnPrefix = $this->entityAliases[$field->getPropertyRefClass()] . '.';
        } else {
            $columnPrefix = '';
        }

        if ($this->query) {
            $this->fieldsMappingCache[$fieldName] = $column = $columnPrefix . $field->getPropertyRefField();
        } else {
            $metadata = $this->entityManager->getClassMetadata($field->getPropertyRefClass());
            $this->fieldsMappingCache[$fieldName] = $column = $columnPrefix . $metadata->getColumnName($field->getPropertyRefField());
        }

        if (isset($this->sqlFieldConversions[$fieldName])) {
            if ($this->query) {
                $this->fieldsMappingCache[$fieldName] = "RECORD_FILTER_FIELD_CONVERSION('$fieldName', $column)";
            } else {
                $this->fieldsMappingCache[$fieldName] = $this->getFieldConversionSql($fieldName, $column, $field);
            }
        }

        return $this->fieldsMappingCache[$fieldName];
    }

    /**
     * Returns either parameter-name or converted value.
     *
     * When there is a conversion and the conversion returns SQL the value is threaded as-is.
     * But if DQL is used the value is wrapped inside an FILTER_VALUE_CONVERSION() DQL function,
     * and executed when the SQL is created.
     *
     * If there is no conversion or the conversion does not return SQL and DQL is used,
     * the value is added as named parameter. SQL always uses the value without parameters.
     *
     * Result is internally cached.
     *
     * @param string      $value
     * @param string      $fieldName
     * @param FilterField $field
     *
     * @return string|float|integer
     */
    protected function getValStr($value, $fieldName, FilterField $field)
    {
        if (!isset($this->sqlValueConversions[$fieldName])) {
            // Set to false by default so we can skip this check the next round
            // Don't use null as isset() returns false then
            $this->sqlValueConversions[$fieldName] = false;

            $classMetadata = $this->metadataFactory->getMetadataForClass($field->getPropertyRefClass());
            $propertyName = $field->getPropertyRefField();

            if (isset($classMetadata->propertyMetadata[$propertyName]) && $classMetadata->propertyMetadata[$propertyName]->hasSqlConversion()) {
                $this->sqlValueConversions[$fieldName] = array(
                    $this->container->get($classMetadata->propertyMetadata[$propertyName]->getSqlConversionService()),
                    $classMetadata->propertyMetadata[$propertyName]->getSqlConversionParams()
                );
            }
        }

        $type = $this->entityManager->getClassMetadata($field->getPropertyRefClass())->getTypeOfField($field->getPropertyRefField());
        if (!is_object($type)) {
            $type = ORMType::getType($type);
        }

        $paramName = null;

        if ($this->sqlValueConversions[$fieldName]) {
            if ($this->sqlValueConversions[$fieldName][0]->requiresBaseConversion()) {
                $value = $type->convertToDatabaseValue($value, $this->databasePlatform);
            }

            $value = $this->sqlValueConversions[$fieldName][0]->convertValue(
                $value,
                $type,
                $this->entityManager->getConnection(), $this->sqlValueConversions[$fieldName][1]
            );

            if ($this->query) {
                $paramName = $this->getUniqueParameterName($fieldName);
                $this->query->setParameter($paramName, $value);
                $value = ':' . $paramName;
            } elseif (is_string($value)) {
                $value = $this->entityManager->getConnection()->quote($value, 'string');
            }

            if ($this->sqlValueConversions[$fieldName][0] instanceof SqlValueAdvancedConversionInterface) {
                if ($this->query instanceof DqlQuery) {
                    $value = "RECORD_FILTER_VALUE_CONVERSION('$fieldName', $value)";
                } else {
                    $value = $this->getValueConversionSql($fieldName, $value, $field, $type);
                }
            }
        } elseif ($this->query) {
            $paramName = $this->getUniqueParameterName($fieldName);
            $this->query->setParameter($paramName, $value, $type);
            $value = ':' . $paramName;
        } else {
            $value = $type->convertToDatabaseValue($value, $this->databasePlatform);

            // Treat numbers as is
            if (!ctype_digit($value)) {
                $value = $this->entityManager->getConnection()->quote($value, $type->getBindingType());
            }
        }

        return $value;
    }

    /**
     * @param string $fieldName
     *
     * @return integer
     */
    protected function getUniqueParameterName($fieldName)
    {
        if (!isset($this->paramPosition[$fieldName])) {
            $this->paramPosition[$fieldName] = -1;
        }

        return $fieldName . '_' . $this->paramPosition[$fieldName] += 1;
    }
}
