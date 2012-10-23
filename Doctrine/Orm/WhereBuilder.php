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

use Rollerworks\Bundle\RecordFilterBundle\Metadata\Doctrine\OrmConfig;
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
 * @todo Value strategy, to use multiple "value-types" , each one uses an new IN(), null is default, -1 will be used as-is
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

    protected $fieldConversions = array();
    protected $valueConversions = array();

    protected $fieldsMappingCache = array();
    protected $fieldConversionCache = array();

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
     *
     * @return self
     */
    public function setEntityManager(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;

        return $this;
    }

    /**
     * Set the SQL conversion configuration for an field.
     *
     * Only one converter per field, existing one is overwritten.
     *
     * @param string                        $fieldName
     * @param FieldConversionInterface|null $conversionObj
     * @param array                         $params
     *
     * @return self
     */
    public function setFieldConversion($fieldName, FieldConversionInterface $conversionObj = null, array $params = array())
    {
        $this->fieldConversions[$fieldName] = array($conversionObj, $params);

        return $this;
    }

    /**
     * Set the SQL conversion configuration for an field.
     *
     * Only one converter per field, existing one is overwritten.
     *
     * @param string                        $fieldName
     * @param ValueConversionInterface|null $conversionObj
     * @param array                         $params
     *
     * @return self
     */
    public function setValueConversion($fieldName, ValueConversionInterface $conversionObj = null, array $params = array())
    {
        $this->valueConversions[$fieldName] = array($conversionObj, $params);

        return $this;
    }

    /**
     * Returns the WHERE clause for the query.
     *
     * WARNING: Don't set an $query object when using the result in union.
     * Calling this method resets the parameter index counter.
     *
     * @param FormatterInterface $formatter
     * @param array              $entityAliasMapping  An array with the alias-mapping as [class or Bundle:Class] => entity-alias
     * @param OrmQuery|null      $query               ORM Query object (required for DQL).
     *
     * @param string|null        $appendQuery         Place *this value* after the current query when when there is an actual filtering result.
     *                                                The query object will be updated as: current query + $appendQuery + filtering.
     *                                                This value is only used when an query object is set, and SHOULD contain spaces like " WHERE "
     * @param boolean            $resetParameterIndex Set this to false if you want to keep the parameter index when calling this method again.
     *                                                This should only be used using multiple filtering results in the same query.
     *
     * @return null|string
     *
     * @throws \InvalidArgumentException when alias-map is empty but $query is set
     */
    public function getWhereClause(FormatterInterface $formatter, array $entityAliasMapping = array(), OrmQuery $query = null, $appendQuery = null, $resetParameterIndex = true)
    {
        // Convert namespace aliases to the correct className
        foreach ($entityAliasMapping as $entity => $alias) {
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

        if ($resetParameterIndex) {
            $this->paramPosition = array();
        }

        $whereCase = $this->buildWhere($formatter);

        if ($query && $appendQuery && $whereCase) {
            if ($query instanceof DqlQuery) {
                $query->setDQL($query->getDQL() . $appendQuery . $whereCase);
            } else {
                $query->setSQL($query->getSQL() . $appendQuery . $whereCase);
            }
        }

        return $whereCase;
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

        $this->fieldConversionCache[$fieldName] = $this->fieldConversions[$fieldName][0]->getConvertFieldSql(
            $column,
            $type,
            $this->entityManager->getConnection(),
            $this->fieldConversions[$fieldName][1]
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

        return $this->valueConversions[$fieldName][0]->getConvertValuedSql(
            $value,
            $type,
            $this->entityManager->getConnection(),
            $this->valueConversions[$fieldName][1]
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
                $this->initValueConversion($fieldName, $field);

                if ($valuesBag->hasSingleValues()) {
                    $query .= $this->valueToList($valuesBag->getSingleValues(), $column, $fieldName, $field);
                }

                if ($valuesBag->hasExcludes()) {
                    $query .= $this->valueToList($valuesBag->getExcludes(), $column, $fieldName, $field, true);
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
     * Returns either comma-separated list of values or field = value condition list.
     *
     * @param SingleValue[] $values
     * @param string        $column
     * @param string        $fieldName
     * @param FilterField   $field
     * @param boolean       $exclude
     *
     * @return string
     */
    protected function valueToList($values, $column, $fieldName, FilterField $field, $exclude = false)
    {
        $inList = '';

        if ($this->valueConversions[$fieldName][0] instanceof CustomSqlValueConversionInterface) {
            // TODO Implement an value conversion strategy pattern

            if ($this->query instanceof DqlQuery) {
                foreach ($values as $value) {
                    $inList .= sprintf('%s %s %s AND ', $column, ($exclude ? '<>' : '='), $this->getValStr($value->getValue(), $fieldName, $field));
                }

                return $inList;
            } else {
                return $this->createInList($values, $column, $fieldName, $field, $exclude);
            }
        } else {
            return $this->createInList($values, $column, $fieldName, $field, $exclude);
        }
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

        if (!isset($this->fieldConversions[$fieldName])) {
            // Set to false by default so we can skip this check the next round
            // Don't use null as isset() returns false then
            $this->fieldConversions[$fieldName] = false;

            if (($propertyConfig = $this->getPropertyConfig($field)) && $propertyConfig->hasFieldConversion()) {
                $this->fieldConversions[$fieldName] = array($this->container->get($propertyConfig->getFieldConversionService()), $propertyConfig->getFieldConversionParams());
            }
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

        if ($this->fieldConversions[$fieldName]) {
            if ($this->query instanceof DqlQuery) {
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
        $type = $this->entityManager->getClassMetadata($field->getPropertyRefClass())->getTypeOfField($field->getPropertyRefField());
        if (!is_object($type)) {
            $type = ORMType::getType($type);
        }

        $paramName = null;

        if ($this->valueConversions[$fieldName][0]) {
            if ($this->valueConversions[$fieldName][0]->requiresBaseConversion()) {
                $value = $type->convertToDatabaseValue($value, $this->databasePlatform);
            }

            $value = $this->valueConversions[$fieldName][0]->convertValue(
                $value,
                $type,
                $this->entityManager->getConnection(), $this->valueConversions[$fieldName][1]
            );

            if ($this->query) {
                $paramName = $this->getUniqueParameterName($fieldName);
                $this->query->setParameter($paramName, $value);
                $value = ':' . $paramName;
            } elseif (is_string($value)) {
                $value = $this->entityManager->getConnection()->quote($value, 'string');
            }

            if ($this->valueConversions[$fieldName][0] instanceof CustomSqlValueConversionInterface) {
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
     * Initialize value conversion cache for the given field.
     *
     * @param string      $fieldName
     * @param FilterField $field
     */
    protected function initValueConversion($fieldName, FilterField  $field)
    {
        if (!isset($this->valueConversions[$fieldName])) {
            if (($propertyConfig = $this->getPropertyConfig($field)) && $propertyConfig->hasValueConversion()) {
                $this->valueConversions[$fieldName] = array($this->container->get($propertyConfig->getValueConversionService()), $propertyConfig->getValueConversionParams());
            } else {
                // Set to empty by default so we can skip this check the next round
                $this->valueConversions[$fieldName] = array(null, array());
            }
        }
    }

    /**
     * @param SingleValue[] $values
     * @param string        $column
     * @param string        $fieldName
     * @param FilterField   $field
     * @param boolean       $exclude
     *
     * @return string
     */
    private function createInList($values, $column, $fieldName, FilterField $field, $exclude = false)
    {
        $inList = '';
        $total  = count($values);
        $cur    = 1;

        foreach ($values as $value) {
            $inList .= $this->getValStr($value->getValue(), $fieldName, $field) . ($total > $cur ? ', ' : '');
            $cur++;
        }

        if ($exclude) {
            $inList = sprintf('%s NOT IN(%s) AND ', $column, $inList);
        } else {
            $inList = sprintf('%s IN(%s) AND ', $column, $inList);
        }

        return $inList;
    }

    /**
     * Returns the ORM configuration of the property or null when no existent.
     *
     * @param FilterField $field
     *
     * @return OrmConfig|null
     */
    private function getPropertyConfig(FilterField $field)
    {
        $classMetadata = $this->metadataFactory->getMetadataForClass($field->getPropertyRefClass());
        $propertyName = $field->getPropertyRefField();

        if (!isset($classMetadata->propertyMetadata[$propertyName])) {
            return null;
        }

        return $classMetadata->propertyMetadata[$propertyName]->getDoctrineConfig('orm');
    }

    /**
     * @param string $fieldName
     *
     * @return integer
     */
    private function getUniqueParameterName($fieldName)
    {
        if (!isset($this->paramPosition[$fieldName])) {
            $this->paramPosition[$fieldName] = -1;
        }

        return $fieldName . '_' . $this->paramPosition[$fieldName] += 1;
    }
}
