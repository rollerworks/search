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
use Rollerworks\Bundle\RecordFilterBundle\Value\Compare;
use Rollerworks\Bundle\RecordFilterBundle\Value\Range;
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

    protected $fieldData = array(
        'dbType' => null,
        'column' => null,
    );

    /**
     * @var array
     */
    protected $parameters = array();

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
     * Set the SQL conversion configuration for a field.
     *
     * Only one converter per field, existing one is overwritten.
     *
     * @param string                        $fieldName
     * @param FieldConversionInterface|null $conversionObj
     * @param array                         $params        An associative array with parameters to (must NOT start with __)
     *
     * @return self
     */
    public function setFieldConversion($fieldName, FieldConversionInterface $conversionObj = null, array $params = array())
    {
        $this->fieldConversions[$fieldName] = array($conversionObj, $params);

        return $this;
    }

    /**
     * Set the SQL conversion configuration for a field.
     *
     * Only one converter per field, existing one is overwritten.
     *
     * @param string                        $fieldName
     * @param ValueConversionInterface|null $conversionObj
     * @param array                         $params        An associative array with parameters to (must NOT start with __)
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
     * Calling this method resets the parameter index counter, set $resetParameterIndex to false to prevent that.
     *
     * @param FormatterInterface $formatter
     * @param array              $entityAliasMapping An array with the alias-mapping as [class or Bundle:Class] => entity-alias
     * @param OrmQuery|null      $query              An ORM Query object (required for DQL).
     * @param string|null        $appendQuery        Place *this value* after the current query when there is an actual filtering result.
     *                                               The query object will be updated as: current query + $appendQuery + filtering.
     *                                               This value is only used when a query object is set, and SHOULD contain spaces like " WHERE "
     * @param boolean $resetParameterIndex 		     Set to false if you want to keep the parameter index when calling this method again.
     *                                               This should only be used when using multiple filtering results in the same query.
     *
     * @return null|string Returns null when there is no result
     *
     * @throws \InvalidArgumentException When alias-map is empty but $query is set
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
        $this->parameters = array();

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
     * Returns the parameters that where set during the building.
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Returns the SQL for the Field conversion.
     *
     * @internal
     *
     * @param string       $fieldName
     * @param string       $column
     * @param FilterField  $field
     * @param null|integer $strategy
     *
     * @return string
     */
    public function getFieldConversionSql($fieldName, $column, FilterField $field = null, $strategy = null)
    {
        if (isset($this->fieldConversionCache[$fieldName][$strategy])) {
            return $this->fieldConversionCache[$fieldName][$strategy];
        }

        if (!$field) {
            $field = $this->fieldSet->get($fieldName);
        }

        $this->fieldConversionCache[$fieldName][$strategy] = $this->fieldConversions[$fieldName][0]->getConvertFieldSql(
            $column,
            $this->fieldData[$fieldName]['dbType'],
            $this->entityManager->getConnection(),
            $this->fieldConversions[$fieldName][1] + array('__conversion_strategy' => $strategy, '__column' => $this->fieldData[$fieldName]['column'])
        );

        return $this->fieldConversionCache[$fieldName][$strategy];
    }

    /**
     * Returns the SQL for the SQL wrapped-value conversion.
     *
     * @internal
     *
     * @param string       $fieldName
     * @param string       $value
     * @param FilterField  $field
     * @param ORMType      $type
     * @param null|integer $strategy
     *
     * @return string
     */
    public function getValueConversionSql($fieldName, $value, FilterField $field = null, ORMType $type = null, $strategy = null)
    {
        if (!$field) {
            $field = $this->fieldSet->get($fieldName);
        }

        if (!$type) {
            $type = $this->fieldData[$fieldName]['dbType'];
        }

        return $this->valueConversions[$fieldName][0]->getConvertValuedSql(
            $value,
            $type,
            $this->entityManager->getConnection(),
            $this->valueConversions[$fieldName][1] + array('__conversion_strategy' => $strategy, '__column' => $this->fieldData[$fieldName]['column'])
        );
    }

    /**
     * Builds and returns the WHERE clause.
     *
     * Fields that do not have a PropertyReference are ignored.
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

                $this->initFilterField($fieldName, $field);
                $column = ($this->valueConversions[$fieldName][0] instanceof ConversionStrategyInterface ? '' : $this->getFieldColumn($fieldName, $field));

                if ($valuesBag->hasSingleValues()) {
                    $query .= $this->processSingleValues($valuesBag->getSingleValues(), $column, $fieldName, $field);
                }

                if ($valuesBag->hasExcludes()) {
                    $query .= $this->processSingleValues($valuesBag->getExcludes(), $column, $fieldName, $field, true);
                }

                if ($valuesBag->hasRanges()) {
                    $query .= $this->processRanges($valuesBag->getRanges(), $column, $fieldName, $field);
                }

                if ($valuesBag->hasExcludedRanges()) {
                    $query .= $this->processRanges($valuesBag->getExcludedRanges(), $column, $fieldName, $field, true);
                }

                if ($valuesBag->hasCompares()) {
                    $query .= $this->processCompares($valuesBag->getCompares(), $column, $fieldName, $field);
                }
            }

            $query = trim($query, " AND ") . ")\n OR ";
        }

        $query = trim($query, " OR ");

        return '' === $query ? null : $query;
    }

    /**
     * Returns either a comma-separated list of values or a field = value condition list.
     *
     * @param SingleValue[] $values
     * @param string        $column
     * @param string        $fieldName
     * @param FilterField   $field
     * @param boolean       $exclude
     *
     * @return string
     */
    protected function processSingleValues($values, $column, $fieldName, FilterField $field, $exclude = false)
    {
        $inList = '';

        // Remap the values and add as-is values to the result
        if ($this->valueConversions[$fieldName][0] instanceof ConversionStrategyInterface) {
            $hasCustomDql = ($this->valueConversions[$fieldName][0] instanceof CustomSqlValueConversionInterface && $this->query instanceof DqlQuery);
            $remappedValues = array();
            $remappedColumns = array();

            $type = $this->fieldData[$fieldName]['dbType'];

            foreach ($values as $value) {
                $strategy = $this->valueConversions[$fieldName][0]->getConversionStrategy($value->getValue(), $type, $this->entityManager->getConnection(), $this->valueConversions[$fieldName][1]);
                $remappedColumns[$strategy] = $this->getFieldColumn($fieldName, $field, $strategy);

                if (0 === $strategy) {
                    $inList .= sprintf('%s AND ', $this->getValStr($value->getValue(), $fieldName, $field, 0));
                } elseif ($hasCustomDql) {
                    $inList .= sprintf('%s %s %s AND ', $column, ($exclude ? '<>' : '='), $this->getValStr($value->getValue(), $fieldName, $field, $strategy));
                } else {
                    $remappedValues[$strategy][] = $value;
                }
            }

            foreach ($remappedValues as $strategy => $value) {
                $inList .= $this->createInList($value, $remappedColumns[$strategy], $fieldName, $field, $exclude, $strategy);
            }

            return $inList;
        }

        if ($this->valueConversions[$fieldName][0] instanceof CustomSqlValueConversionInterface) {
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
     * @param Range[]     $ranges
     * @param string      $column
     * @param string      $fieldName
     * @param FilterField $field
     * @param boolean     $exclude
     *
     * @return string
     */
    protected function processRanges($ranges, $column, $fieldName, FilterField $field, $exclude = false)
    {
        $query = '';

        if ($this->valueConversions[$fieldName][0] instanceof ConversionStrategyInterface) {
            $type = $this->fieldData[$fieldName]['dbType'];

            foreach ($ranges as $range) {
                $strategy = $this->valueConversions[$fieldName][0]->getConversionStrategy($range->getLower(), $type, $this->entityManager->getConnection(), $this->valueConversions[$fieldName][1]);
                $column = $this->getFieldColumn($fieldName, $field, $strategy);

                if ($exclude) {
                    $query .= sprintf('(%s NOT BETWEEN %s AND %s) AND ', $column, $this->getValStr($range->getLower(), $fieldName, $field, $strategy), $this->getValStr($range->getUpper(), $fieldName, $field, $strategy));
                } else {
                    $query .= sprintf('(%s BETWEEN %s AND %s) AND ', $column, $this->getValStr($range->getLower(), $fieldName, $field, $strategy), $this->getValStr($range->getUpper(), $fieldName, $field, $strategy));
                }
            }

            return $query;
        }

        foreach ($ranges as $range) {
            if ($exclude) {
                $query .= sprintf('(%s NOT BETWEEN %s AND %s) AND ', $column, $this->getValStr($range->getLower(), $fieldName, $field), $this->getValStr($range->getUpper(), $fieldName, $field));
            } else {
                $query .= sprintf('(%s BETWEEN %s AND %s) AND ', $column, $this->getValStr($range->getLower(), $fieldName, $field), $this->getValStr($range->getUpper(), $fieldName, $field));
            }
        }

        return $query;
    }

    /**
     * @param Compare[]   $compares
     * @param string      $column
     * @param string      $fieldName
     * @param FilterField $field
     *
     * @return string
     */
    protected function processCompares($compares, $column, $fieldName, FilterField $field)
    {
        $query = '';

        if ($this->valueConversions[$fieldName][0] instanceof ConversionStrategyInterface) {
            $type = $this->fieldData[$fieldName]['dbType'];

            foreach ($compares as $comp) {
                $strategy = $this->valueConversions[$fieldName][0]->getConversionStrategy($comp->getValue(), $type, $this->entityManager->getConnection(), $this->valueConversions[$fieldName][1]);
                $column = $this->getFieldColumn($fieldName, $field, $strategy);

                $query .= sprintf('%s %s %s AND ', $column, $comp->getOperator(), $this->getValStr($comp->getValue(), $fieldName, $field, $strategy));
            }

            return $query;
        }

        foreach ($compares as $comp) {
            $query .= sprintf('%s %s %s AND ', $column, $comp->getOperator(), $this->getValStr($comp->getValue(), $fieldName, $field));
        }

        return $query;
    }

    /**
     * Returns the correct column (with SQLField conversions applied).
     *
     * @param string       $fieldName
     * @param FilterField  $field
     * @param integer|null $strategy
     *
     * @return string
     */
    protected function getFieldColumn($fieldName, FilterField $field, $strategy = null)
    {
        if (isset($this->fieldsMappingCache[$fieldName][$strategy])) {
            return $this->fieldsMappingCache[$fieldName][$strategy];
        }

        $columnPrefix = '';
        $column = $field->getPropertyRefField();

        // Resolve the referencedColumnName for Join
        $metaData = $this->entityManager->getClassMetadata($field->getPropertyRefClass());
        if ($this->query instanceof DqlQuery && $metaData->isAssociationWithSingleJoinColumn($field->getPropertyRefField())) {
            $joiningClass = $metaData->getAssociationTargetClass($field->getPropertyRefField());
            if (!isset($this->entityAliases[$joiningClass])) {
                throw new \RuntimeException(sprintf('No alias mapping set for "%s", used by "%s"#%s Join.', $joiningClass, $field->getPropertyRefClass(), $field->getPropertyRefField()));
            }

            $columnPrefix = $this->entityAliases[$joiningClass] . '.';
            $column = $metaData->getSingleAssociationReferencedJoinColumnName($field->getPropertyRefField());;
        } elseif (isset($this->entityAliases[$field->getPropertyRefClass()])) {
            $columnPrefix = $this->entityAliases[$field->getPropertyRefClass()] . '.';
        }

        if ($this->query) {
            $this->fieldsMappingCache[$fieldName][$strategy] = $column = $columnPrefix . $column;
        } else {
            $metadata = $this->entityManager->getClassMetadata($field->getPropertyRefClass());
            $this->fieldsMappingCache[$fieldName][$strategy] = $column = $columnPrefix . $metadata->getColumnName($column);
        }
        $this->fieldData[$fieldName]['column'] = $column;

        if ($this->fieldConversions[$fieldName]) {
            if ($this->query instanceof DqlQuery) {
                $this->fieldsMappingCache[$fieldName][$strategy] = "RECORD_FILTER_FIELD_CONVERSION('$fieldName', $column" . (null === $strategy ? '' : ', ' . $strategy) . ")";
            } else {
                $this->fieldsMappingCache[$fieldName][$strategy] = $this->getFieldConversionSql($fieldName, $column, $field, $strategy);
            }
        }

        return $this->fieldsMappingCache[$fieldName][$strategy];
    }

    /**
     * Returns either a parameter-name or converted value.
     *
     * When there is a conversion and the conversion returns SQL the value is threaded as-is.
     * But if DQL is used the value is wrapped inside a FILTER_VALUE_CONVERSION() DQL function,
     * and replaced when the SQL is created.
     *
     * If there is no conversion or the conversion does not return SQL and DQL is used,
     * the value is added as a named parameter.
     *
     * @param string       $value
     * @param string       $fieldName
     * @param FilterField  $field
     * @param integer|null $strategy
     *
     * @return string|float|integer
     */
    protected function getValStr($value, $fieldName, FilterField $field, $strategy = null)
    {
        /** @var \Doctrine\DBAL\Types\Type $type */
        $type = $this->fieldData[$fieldName]['dbType'];
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
                $this->parameters[$paramName] = $value;
                $value = ':' . $paramName;
            } elseif (is_string($value)) {
                $value = $this->entityManager->getConnection()->quote($value, 'string');
            }

            if ($this->valueConversions[$fieldName][0] instanceof CustomSqlValueConversionInterface) {
                if ($this->query instanceof DqlQuery) {
                    $value = "RECORD_FILTER_VALUE_CONVERSION('$fieldName', $value" . (null === $strategy ? '' : ', ' . $strategy) . ")";
                } else {
                    $value = $this->getValueConversionSql($fieldName, $value, $field, $type, $strategy);
                }
            }
        } elseif ($this->query) {
            $paramName = $this->getUniqueParameterName($fieldName);
            $this->query->setParameter($paramName, $value, $type);
            $this->parameters[$paramName] = $value;
            $value = ':' . $paramName;
        } else {
            $value = $type->convertToDatabaseValue($value, $this->databasePlatform);

            // Treat numbers as-is
            if (!ctype_digit($value)) {
                $value = $this->entityManager->getConnection()->quote($value, $type->getBindingType());
            }
        }

        return $value;
    }

    /**
     * Initialize the filtering-field.
     *
     * This Initializes the conversion cache and internal data.
     *
     * @param string      $fieldName
     * @param FilterField $field
     */
    protected function initFilterField($fieldName, FilterField  $field)
    {
        if (!isset($this->valueConversions[$fieldName])) {
            if (($propertyConfig = $this->getPropertyConfig($field)) && $propertyConfig->hasValueConversion()) {
                $this->valueConversions[$fieldName] = array($this->container->get($propertyConfig->getValueConversionService()), $propertyConfig->getValueConversionParams());
            } else {
                // Set to empty by default so we can skip this check the next round
                $this->valueConversions[$fieldName] = array(null, array());
            }
        }

        if (!isset($this->fieldConversions[$fieldName])) {
            // Set to false by default so we can skip this check the next round
            // Don't use null as isset() returns false then
            $this->fieldConversions[$fieldName] = false;

            if (($propertyConfig = $this->getPropertyConfig($field)) && $propertyConfig->hasFieldConversion()) {
                $this->fieldConversions[$fieldName] = array($this->container->get($propertyConfig->getFieldConversionService()), $propertyConfig->getFieldConversionParams());
            }
        }

        if (!isset($this->fieldData[$fieldName]['dbType'])) {
            $metaData = $this->entityManager->getClassMetadata($field->getPropertyRefClass());
            if (!($type = $metaData->getTypeOfField($field->getPropertyRefField()))) {
                // As there is no type the only logical part is a JOIN, but we only can process a single Column JOIN
                if (!$metaData->isAssociationWithSingleJoinColumn($field->getPropertyRefField())) {
                    throw new \RuntimeException('Unable to get Type of none-single column Join.');
                }

                $joiningClass = $metaData->getAssociationTargetClass($field->getPropertyRefField());
                $referencedColumnName = $metaData->getSingleAssociationReferencedJoinColumnName($field->getPropertyRefField());
                $type = $this->entityManager->getClassMetadata($joiningClass)->getTypeOfField($referencedColumnName);
            }

            if (!is_object($type)) {
                $type = ORMType::getType($type);
            }

            $this->fieldData[$fieldName]['dbType'] = $type;
        }
    }

    /**
     * @param SingleValue[] $values
     * @param string        $column
     * @param string        $fieldName
     * @param FilterField   $field
     * @param boolean       $exclude
     * @param integer|null  $strategy
     *
     * @return string
     */
    private function createInList($values, $column, $fieldName, FilterField $field, $exclude = false, $strategy = null)
    {
        $inList = '';
        $total  = count($values);
        $cur    = 1;

        foreach ($values as $value) {
            $inList .= $this->getValStr($value->getValue(), $fieldName, $field, $strategy) . ($total > $cur ? ', ' : '');
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
     * Returns the ORM configuration of the property or null when none is set.
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
