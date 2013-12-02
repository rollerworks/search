<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search\Doctrine\Orm;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\DBAL\Types\Type as ORMType;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\NativeQuery;
use Doctrine\ORM\Query as DqlQuery;
use Doctrine\ORM\QueryBuilder;
use Rollerworks\Component\Search\Doctrine\Dbal\ConversionStrategyInterface;
use Rollerworks\Component\Search\Doctrine\Dbal\SqlFieldConversionInterface;
use Rollerworks\Component\Search\Doctrine\Dbal\SqlValueConversionInterface;
use Rollerworks\Component\Search\Doctrine\Dbal\ValueConversionInterface;
use Rollerworks\Component\Search\Exception\BadMethodCallException;
use Rollerworks\Component\Search\Exception\InvalidConfigurationException;
use Rollerworks\Component\Search\Exception\UnexpectedTypeException;
use Rollerworks\Component\Search\Exception\UnknownFieldException;
use Rollerworks\Component\Search\FieldConfigInterface;
use Rollerworks\Component\Search\SearchConditionInterface;
use Rollerworks\Component\Search\Value\Compare;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\Value\Range;
use Rollerworks\Component\Search\Value\SingleValue;
use Rollerworks\Component\Search\ValuesGroup;

/**
 * SearchCondition Doctrine ORM WhereBuilder.
 *
 * This class provides the functionality for creating an SQL/DQL WHERE-clause
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
class WhereBuilder implements WhereBuilderInterface
{
    /**
     * @var SearchConditionInterface
     */
    private $searchCondition;

    /**
     * @var \Rollerworks\Component\Search\FieldSet
     */
    private $fieldset;

    /**
     * @var NativeQuery|DqlQuery|QueryBuilder
     */
    private $query;

    /**
     * @var boolean
     */
    private $queryModified;

    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var array
     */
    private $entityClassMapping = array();

    /**
     * @var array
     */
    private $entityFieldMapping = array();

    /**
     * @var ValueConversionInterface[]|SqlValueConversionInterface[]|ConversionStrategyInterface[]
     */
    private $valueConversions = array();

    /**
     * @var SqlFieldConversionInterface[]
     */
    private $fieldConversions = array();

    /**
     * @var string
     */
    private $parameterPrefix;

    /**
     * @var string
     */
    private $whereClause;

    /**
     * @var array
     */
    private $fieldsData = array();

    /**
     * @var array
     */
    private $parameters = array();

    /**
     * @var array
     */
    private $fieldsMappingCache = array();

    /**
     * @var array
     */
    private $fieldConversionCache = array();

    /**
     * @var array
     */
    private $paramPosition = array();

    /**
     * Constructor.
     *
     * @param NativeQuery|DqlQuery|QueryBuilder $query           Doctrine ORM Query or QueryBuilder object
     * @param SearchConditionInterface          $searchCondition SearchCondition object
     *
     * @throws BadMethodCallException  When SearchCondition contains errors.
     * @throws UnexpectedTypeException When $query is an invalid type.
     */
    public function __construct($query, SearchConditionInterface $searchCondition)
    {
        if ($searchCondition->getValuesGroup()->hasErrors()) {
            throw new BadMethodCallException('Unable to generate the where-clause, because the SearchCondition contains errors.');
        }

        if (!$query instanceof QueryBuilder && !$query instanceof AbstractQuery) {
            throw new UnexpectedTypeException($query, 'Doctrine\ORM\Query, Doctrine\ORM\NativeQuery or Doctrine\ORM\QueryBuilder');
        }

        $this->searchCondition = $searchCondition;
        $this->fieldset = $searchCondition->getFieldSet();
        $this->entityManager = $query->getEntityManager();
        $this->query = $query;
    }

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
     *
     * @throws BadMethodCallException When the where-clause is already generated.
     */
    public function setEntityMappings(array $mapping)
    {
        if ($this->whereClause) {
            throw new BadMethodCallException('WhereBuilder configuration methods cannot be accessed anymore once the where-clause is generated.');
        }

        $this->entityClassMapping = $mapping;

        return $this;
    }

    /**
     * Set the entity mapping per class.
     *
     * @param string $entity class or Doctrine alias
     * @param string $alias  Entity alias as used in the query
     *
     * @return self
     *
     * @throws BadMethodCallException When the where-clause is already generated.
     */
    public function setEntityMapping($entity, $alias)
    {
        if ($this->whereClause) {
            throw new BadMethodCallException('WhereBuilder configuration methods cannot be accessed anymore once the where-clause is generated.');
        }

        $this->entityClassMapping[$entity] = $alias;

        return $this;
    }

    /**
     * Set the entity mapping for a field.
     *
     * Use this method for a more explicit mapping.
     * By setting the mapping for the field, the builder
     * will use the specific alias instead of the globally configured one.
     *
     * Example if ClassA is mapped to alias A, but FieldB (model A)
     * needs a special alias reference you can set it as alias FieldB => AB.
     *
     * @param string      $fieldName FieldName as registered in the fieldset
     * @param string|null $alias     Set to the null to remove the mapping
     *
     * @return self
     *
     * @throws BadMethodCallException When the where-clause is already generated.
     */
    public function setFieldMapping($fieldName, $alias)
    {
        if ($this->whereClause) {
            throw new BadMethodCallException('WhereBuilder configuration methods cannot be accessed anymore once the where-clause is generated.');
        }

        if (null === $alias) {
            unset($this->entityFieldMapping[$fieldName]);
        }

        $this->entityFieldMapping[$fieldName] = $alias;

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
     * @return self
     *
     * @throws UnknownFieldException  When the field is not registered in the fieldset.
     * @throws BadMethodCallException When the where-clause is already generated.
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
     * Set the prefix to prefix the query-parameters with.
     *
     * This will be applied as: prefix + fieldname + group + value-index.
     * Example: user_id_0_1
     *
     * @param string $prefix
     *
     * @return self
     *
     * @throws BadMethodCallException when the where-clause is already generated
     */
    public function setParameterPrefix($prefix)
    {
        if ($this->whereClause) {
            throw new BadMethodCallException('WhereBuilder configuration methods cannot be accessed anymore once the where-clause is generated.');
        }

        $this->parameterPrefix = $prefix;
    }

    /**
     * Returns the generated where-clause.
     *
     * The Where-clause is wrapped inside a group so it
     * can be safely used with other conditions.
     *
     * If you use DQL, you should also set the required hints using
     * getQueryHintName() and getQueryHintValue() respectively.
     *
     * @return string
     */
    public function getWhereClause()
    {
        if (null !== $this->whereClause) {
            return $this->whereClause;
        }

        $this->fieldsMappingCache   = array();
        $this->fieldConversionCache = array();

        // Resolve the EntityClassMappings to a real class-name.
        foreach ($this->entityClassMapping as $class => $alias) {
            $realClass = $this->resolveEntityClass($class);

            if ($realClass !== $class) {
                $this->entityClassMapping[$realClass] = $alias;
                unset($this->entityClassMapping[$class]);
            }
        }

        // Initialize the information for the fields.
        foreach ($this->fieldset->all() as $fieldName => $fieldConfig) {
            $field = $this->fieldset->get($fieldName);
            if (null === $field->getModelRefClass()) {
                continue;
            }

            $this->fieldsData[$fieldName] = array();
            $this->fieldsData[$fieldName]['dbType'] = $this->getDbType($field->getModelRefClass(), $field->getModelRefProperty());
            $this->fieldsData[$fieldName]['column'] = $this->resolveFieldColumn($field->getModelRefClass(), $field->getModelRefProperty(), $fieldName);
        }

        $this->whereClause = $this->processGroup($this->searchCondition->getValuesGroup());

        return $this->whereClause;
    }

    /**
     * Updates the configured query object with the where-clause.
     *
     * Note. The QueryBuilder does not support configuring hints (used by conversion)
     * so you should set these manually on the final query object.
     * Use getQueryHintName() and getQueryHintValue() respectively.
     *
     * Note. When the query is already updated this will do nothing.
     *
     * @param string  $prependQuery Prepends this string to the where-clause ("WHERE" or "AND" for example)
     * @param boolean $forceUpdate  Force the where-builder to update the query
     *
     * @return self
     */
    public function updateQuery($prependQuery = '', $forceUpdate = false)
    {
        $whereCase = $this->getWhereClause();

        if ($whereCase === '' || ($this->queryModified && !$forceUpdate)) {
            return $this;
        }

        if ($this->query instanceof NativeQuery) {
            $this->query->setSQL($this->query->getSQL() . $prependQuery . $whereCase);
        } else {
            $this->query->setDQL($this->query->getDQL() . $prependQuery . $whereCase);
        }

        if ($this->query instanceof DqlQuery) {
            $this->query->setHint($this->getQueryHintName(), $this->getQueryHintValue());
        }

        $this->queryModified = true;

        return $this;
    }

    /**
     * Returns the parameters that where set during the generation process.
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * Returns the parameter-value that where set during the generation process.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getParameter($name)
    {
        return isset($this->parameters[$name]) ? $this->parameters[$name] : null;
    }

    /**
     * Returns the Query hint name for the final query object.
     *
     * The Query hint is used for conversions.
     *
     * @return string
     */
    public function getQueryHintName()
    {
        return 'rw_where_builder';
    }

    /**
     * Returns the Query hint value for the final query object.
     *
     * The Query hint is used for conversions for value-matchers.
     *
     * @return \Closure
     */
    public function getQueryHintValue()
    {
        $self = $this;

        // We use a closure here to prevent a nesting recursion
        return function () use (&$self) { return $self; };
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->entityManager;
    }

    /**
     * @return SearchConditionInterface
     */
    public function getSearchCondition()
    {
        return $this->searchCondition;
    }

    /**
     * @return NativeQuery|DqlQuery|QueryBuilder
     */
    public function getQuery()
    {
        return $this->query;
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

    /**
     * Returns the SQL for the Field conversion.
     *
     * @internal
     *
     * @param string               $fieldName
     * @param string               $column
     * @param FieldConfigInterface $field
     * @param null|integer         $strategy
     *
     * @return string
     */
    public function getFieldConversionSql($fieldName, $column, FieldConfigInterface $field = null, $strategy = null)
    {
        if (isset($this->fieldConversionCache[$fieldName][$strategy])) {
            return $this->fieldConversionCache[$fieldName][$strategy];
        }

        $field = $field ?: $this->fieldset->get($fieldName);

        $hints = array(
            'searchField' => $field,
            'connection' => $this->entityManager->getConnection(),
            'dbType' => $this->fieldsData[$fieldName]['dbType'],
            'conversionStrategy' => $strategy,
        );

        $this->fieldConversionCache[$fieldName][$strategy] = $this->fieldConversions[$fieldName]->convertSqlField(
            $column,
            $field->getOptions(),
            $hints
        );

        return $this->fieldConversionCache[$fieldName][$strategy];
    }

    /**
     * Returns the SQL for the SQL wrapped-value conversion.
     *
     * @internal
     *
     * @param string               $fieldName
     * @param string               $column
     * @param string               $value
     * @param FieldConfigInterface $field
     * @param null|integer         $strategy
     * @param boolean              $isValueEmbedded
     *
     * @return string
     *
     * @throws BadMethodCallException
     */
    public function getValueConversionSql($fieldName, $column, $value, FieldConfigInterface $field = null, $strategy = null, $isValueEmbedded = false)
    {
        $field = $field ?: $this->fieldset->get($fieldName);

        $hints = array(
            'searchField' => $field,
            'connection' => $this->entityManager->getConnection(),
            'dbType' => $this->fieldsData[$fieldName]['dbType'],
            'column' => $column,
            'conversionStrategy' => $strategy,
        );

        if ($isValueEmbedded) {
            if (!isset($this->parameters[$value])) {
                throw new BadMethodCallException(sprintf('Unable to find query-parameter "%s", requested for embedding by ValueConversion.', $value));
            }

            $value = $this->parameters[$value];
        }

        return $this->valueConversions[$fieldName]->convertSqlValue(
            $value,
            $field->getOptions(),
            $hints
        );
    }

    /**
     * @param ValuesGroup $valuesGroup
     *
     * @return string
     */
    private function processGroup(ValuesGroup $valuesGroup)
    {
        $query = '';

        foreach ($valuesGroup->getFields() as $fieldName => $values) {
            $field = $this->fieldset->get($fieldName);
            if (null === $field->getModelRefClass()) {
                continue;
            }

            if (isset($this->fieldConversions[$fieldName])) {
                $column = $this->getFieldColumn($fieldName, $field);
            } else {
                $column = $this->fieldsData[$fieldName]['column'];
            }

            $groupSql = '';
            $inclusiveSqlGroup = '';
            $exclusiveSqlGroup = '';

            if ($values->hasSingleValues()) {
                $inclusiveSqlGroup .= $this->processSingleValues($values->getSingleValues(), $column, $fieldName, $field);
            }

            if ($values->hasRanges()) {
                $inclusiveSqlGroup .= $this->processRanges($values->getRanges(), $column, $fieldName, $field);
            }

            if ($values->hasComparisons()) {
                $inclusiveSqlGroup .= $this->processCompares($values->getComparisons(), $column, $fieldName, $field);
            }

            if ($values->hasPatternMatchers()) {
                $inclusiveSqlGroup .= $this->processPatternMatchers($values->getPatternMatchers(), $column, $fieldName, $field);
            }

            if (!empty($inclusiveSqlGroup)) {
                $inclusiveSqlGroup = rtrim($inclusiveSqlGroup, ' OR ');
                $groupSql .= "($inclusiveSqlGroup)";
            }

            if ($values->hasExcludedValues()) {
                $exclusiveSqlGroup .= $this->processSingleValues($values->getExcludedValues(), $column, $fieldName, $field, true);
            }

            if ($values->hasExcludedRanges()) {
                $exclusiveSqlGroup .= $this->processRanges($values->getExcludedRanges(), $column, $fieldName, $field, true);
            }

            if ($values->hasPatternMatchers()) {
                $exclusiveSqlGroup .= $this->processPatternMatchers($values->getPatternMatchers(), $column, $fieldName, $field, true);
            }

            if ($exclusiveSqlGroup) {
                $exclusiveSqlGroup = rtrim($exclusiveSqlGroup, ' AND ');
                if ($inclusiveSqlGroup) {
                    $groupSql .= " AND ";
                }
                $groupSql .= "($exclusiveSqlGroup)";
            }

            if ($inclusiveSqlGroup or $exclusiveSqlGroup) {
                $query .= "($groupSql)";
            }

            if ($groupSql) {
                $query .= ValuesGroup::GROUP_LOGICAL_OR === $valuesGroup->getGroupLogical() ? ' OR ' : ' AND ';
            }
        }

        // Wrap all the fields as a group
        if ($query) {
            // Remove the last logical statement
            if (ValuesGroup::GROUP_LOGICAL_OR === $valuesGroup->getGroupLogical()) {
                $query = substr($query, 0, -4);
            } else {
                $query = substr($query, 0, -5);
            }

            $query = "($query)";
        }

        if ($valuesGroup->hasGroups()) {
            $groupSql = '';

            foreach ($valuesGroup->getGroups() as $group) {
                $groupSql .= $this->processGroup($group);
                $groupSql .= ' OR ';
            }

            if ($groupSql) {
                $groupSql = rtrim($groupSql, ' OR ');

                if ($query) {
                    $query .= " AND ($groupSql)";
                } else {
                    $query .= $groupSql;
                }
            }

            // Now wrap it one more time to finalize the group
            if ($query) {
                $query = "($query)";
            }
        }

        return $query;
    }

    /**
     * Processes the single-values and returns an IN() SQL statement and/or strategic query result.
     *
     * @param SingleValue[]        $values
     * @param string               $column
     * @param string               $fieldName
     * @param FieldConfigInterface $field
     * @param boolean              $exclude
     *
     * @return string
     */
    private function processSingleValues($values, $column, $fieldName, FieldConfigInterface $field, $exclude = false)
    {
        $inList = '';

        if (isset($this->valueConversions[$fieldName])) {
            // Remap the values and add as-is values to the result
            if ($this->valueConversions[$fieldName] instanceof ConversionStrategyInterface) {
                $hasCustomDql = ($this->valueConversions[$fieldName] instanceof SqlValueConversionInterface);
                $remappedValues = array();
                $remappedColumns = array();

                $hints = array(
                    'searchField' => $field,
                    'connection' => $this->entityManager->getConnection(),
                    'dbType' => $this->fieldsData[$fieldName]['dbType'],
                );

                foreach ($values as $value) {
                    $strategy = $this->valueConversions[$fieldName]->getConversionStrategy($value->getValue(), $field->getOptions(), $hints);
                    $remappedColumns[$strategy] = $this->getFieldColumn($fieldName, $field, $strategy);

                    if ($hasCustomDql) {
                        $inList .= sprintf('%s %s %s %s ', $remappedColumns[$strategy], ($exclude ? '<>' : '='), $this->getValueAsString($value->getValue(), $value, $fieldName, $field, $strategy), ($exclude ? 'AND' : 'OR'));
                    } else {
                        $remappedValues[$strategy][] = $value;
                    }
                }

                foreach ($remappedValues as $strategy => $value) {
                    $inList .= $this->createInList($value, $remappedColumns[$strategy], $fieldName, $field, $exclude, $strategy);
                }

                return $inList;
            }

            // Don't use IN() with a custom sql-statement for better compatibility
            if ($this->valueConversions[$fieldName] instanceof SqlValueConversionInterface) {
                foreach ($values as $value) {
                    $inList .= sprintf('%s %s %s %s ', $column, ($exclude ? '<>' : '='), $this->getValueAsString($value->getValue(), $value, $fieldName, $field), ($exclude ? 'AND' : 'OR'));
                }

                return $inList;
            }
        }

        return $this->createInList($values, $column, $fieldName, $field, $exclude);
    }

    /**
     * @param SingleValue[]        $values
     * @param string               $column
     * @param string               $fieldName
     * @param FieldConfigInterface $field
     * @param boolean              $exclude
     * @param integer|null         $strategy
     *
     * @return string
     */
    private function createInList($values, $column, $fieldName, FieldConfigInterface $field, $exclude = false, $strategy = null)
    {
        $inList = '';
        $total  = count($values);
        $cur    = 1;

        foreach ($values as $value) {
            $inList .= $this->getValueAsString($value->getValue(), $value, $fieldName, $field, $strategy) . ($total > $cur ? ', ' : '');
            $cur++;
        }

        if ($exclude) {
            $inList = sprintf('%s NOT IN(%s) AND ', $column, $inList);
        } else {
            $inList = sprintf('%s IN(%s) OR ', $column, $inList);
        }

        return $inList;
    }

    /**
     * @param Range[]              $ranges
     * @param string               $column
     * @param string               $fieldName
     * @param FieldConfigInterface $field
     * @param boolean              $exclude
     *
     * @return string
     */
    private function processRanges($ranges, $column, $fieldName, FieldConfigInterface $field, $exclude = false)
    {
        $query = '';

        if (isset($this->valueConversions[$fieldName]) && $this->valueConversions[$fieldName] instanceof ConversionStrategyInterface) {
            $hints = array(
                'searchField' => $field,
                'connection' => $this->entityManager->getConnection(),
                'dbType' => $this->fieldsData[$fieldName]['dbType'],
            );

            foreach ($ranges as $range) {
                $strategy = $this->valueConversions[$fieldName]->getConversionStrategy($range->getLower(), $field->getOptions(), $hints);
                $column = $this->getFieldColumn($fieldName, $field, $strategy);

                $query .= sprintf(
                    $this->getRangePattern($range, $exclude),
                    $column,
                    $this->getValueAsString($range->getLower(), $range, $fieldName, $field, $strategy),
                    $column,
                    $this->getValueAsString($range->getUpper(), $range, $fieldName, $field, $strategy)
                ) . ' OR ';
            }

            if ($query) {
                $query = substr($query, 0, -4);
                $query = "($query)";
            }

            return $query;
        }

        foreach ($ranges as $range) {
            $query .= sprintf(
                $this->getRangePattern($range, $exclude),
                $column,
                $this->getValueAsString($range->getLower(), $range, $fieldName, $field),
                $column,
                $this->getValueAsString($range->getUpper(), $range, $fieldName, $field)
            ) . ($exclude ? ' AND ' : ' OR ');
        }

        return $query;
    }

    /**
     * @param Range   $range
     * @param boolean $exclude
     *
     * @return string "(%s >= %s AND %s <= %s)"
     */
    private function getRangePattern(Range $range, $exclude = false)
    {
        $pattern = '(%s ';

        if ($exclude) {
            $pattern .= ($range->isLowerInclusive() ? '<=' : '<');
            $pattern .= ' %s OR %s '; // lower-bound value, AND fieldname
            $pattern .= ($range->isUpperInclusive() ? '>=' : '>');
            $pattern .= ' %s'; // upper-bound value
        } else {
            $pattern .= ($range->isLowerInclusive() ? '>=' : '>');
            $pattern .= ' %s AND %s '; // lower-bound value, AND fieldname
            $pattern .= ($range->isUpperInclusive() ? '<=' : '<');
            $pattern .= ' %s'; // upper-bound value
        }

        $pattern .= ')';

        return $pattern;
    }

    /**
     * @param Compare[]            $compares
     * @param string               $column
     * @param string               $fieldName
     * @param FieldConfigInterface $field
     *
     * @return string
     */
    private function processCompares($compares, $column, $fieldName, FieldConfigInterface $field)
    {
        $query = '';

        if (isset($this->valueConversions[$fieldName]) && $this->valueConversions[$fieldName] instanceof ConversionStrategyInterface) {
            $hints = array(
                'searchField' => $field,
                'connection' => $this->entityManager->getConnection(),
                'dbType' => $this->fieldsData[$fieldName]['dbType'],
            );

            foreach ($compares as $comparison) {
                $strategy = $this->valueConversions[$fieldName]->getConversionStrategy($comparison->getValue(), $field->getOptions(), $hints);
                $column = $this->getFieldColumn($fieldName, $field, $strategy);

                $query .= sprintf('%s %s %s OR ', $column, $comparison->getOperator(), $this->getValueAsString($comparison->getValue(), $comparison, $fieldName, $field, $strategy));
            }

            if ($query) {
                $query = substr($query, 0, -4);
                $query = "($query)";
            }

            return $query;
        }

        foreach ($compares as $comparison) {
            $query .= sprintf('%s %s %s OR ', $column, $comparison->getOperator(), $this->getValueAsString($comparison->getValue(), $comparison, $fieldName, $field));
        }

        return $query;
    }

    /**
     * @param PatternMatch[]       $patternMatchers
     * @param string               $column
     * @param string               $fieldName
     * @param FieldConfigInterface $field
     * @param boolean              $exclude
     *
     * @return string
     */
    private function processPatternMatchers($patternMatchers, $column, $fieldName, FieldConfigInterface $field, $exclude = false)
    {
        $query = '';

        if (isset($this->valueConversions[$fieldName]) && $this->valueConversions[$fieldName] instanceof ConversionStrategyInterface) {
            $hints = array(
                'searchField' => $field,
                'connection' => $this->entityManager->getConnection(),
                'dbType' => $this->fieldsData[$fieldName]['dbType'],
            );

            foreach ($patternMatchers as $patternMatch) {
                $isExclusive = $patternMatch->isExclusive();
                if ((!$exclude && $isExclusive) xor ($exclude && !$isExclusive)) {
                    continue;
                }

                $strategy = $this->valueConversions[$fieldName]->getConversionStrategy($patternMatch->getValue(), $field->getOptions(), $hints);
                $column = $this->getFieldColumn($fieldName, $field, $strategy);

                $query .= sprintf($this->getPatternMatcherPattern($patternMatch), $column, $this->getValueAsString($patternMatch->getValue(), $patternMatch, $fieldName, $field, $strategy, true));
                $query .= ($exclude ? ' AND ' : ' OR ');
            }

            return $query;
        }

        foreach ($patternMatchers as $patternMatch) {
            $isExclusive = $patternMatch->isExclusive();
            if ((!$exclude && $isExclusive) || ($exclude && !$isExclusive)) {
                continue;
            }

            $query .= sprintf($this->getPatternMatcherPattern($patternMatch), $column, $this->getValueAsString($patternMatch->getValue(), $patternMatch, $fieldName, $field, null, true));
            $query .= ($exclude ? ' AND ' : ' OR ');
        }

        return $query;
    }

    /**
     * @param PatternMatch $patternMatch
     *
     * @return string
     */
    private function getPatternMatcherPattern(PatternMatch $patternMatch)
    {
        // Doctrine at the moment does not support case insensitive LIKE or regex match
        // So we use a custom function for this

        $pattern = array(
            PatternMatch::PATTERN_STARTS_WITH => "RW_SEARCH_MATCH(%s, %s, 'starts_with', " . ($patternMatch->isCaseInsensitive() ? 'true' : 'false') .") = 1",
            PatternMatch::PATTERN_NOT_STARTS_WITH => "RW_SEARCH_MATCH(%s, %s', 'starts_with', " . ($patternMatch->isCaseInsensitive() ? 'true' : 'false') .") <> 1",

            PatternMatch::PATTERN_CONTAINS => "RW_SEARCH_MATCH(%s, %s, 'contains', " . ($patternMatch->isCaseInsensitive() ? 'true' : 'false') .") = 1",
            PatternMatch::PATTERN_NOT_CONTAINS => "RW_SEARCH_MATCH(%s, %s, 'contains', " . ($patternMatch->isCaseInsensitive() ? 'true' : 'false') .") <> 1",

            PatternMatch::PATTERN_ENDS_WITH => "RW_SEARCH_MATCH(%s, %s, 'ends_with', " . ($patternMatch->isCaseInsensitive() ? 'true' : 'false') .") = 1",
            PatternMatch::PATTERN_NOT_ENDS_WITH => "RW_SEARCH_MATCH(%s, %s, 'ends_with', " . ($patternMatch->isCaseInsensitive() ? 'true' : 'false') .") <> 1",

            PatternMatch::PATTERN_REGEX => "RW_SEARCH_MATCH(%s, %s, 'regex', " . ($patternMatch->isCaseInsensitive() ? 'true' : 'false') .") = 1",
            PatternMatch::PATTERN_NOT_REGEX => "RW_SEARCH_MATCH(%s, %s, 'regex', " . ($patternMatch->isCaseInsensitive() ? 'true' : 'false') .") <> 1",
        );

        return $pattern[$patternMatch->getType()];
    }

    /**
     * Returns either a parameter-name or converted value.
     *
     * When there is a conversion and the conversion returns SQL the value is threaded as-is.
     * But if DQL is used the value is wrapped inside a FILTER_VALUE_CONVERSION() DQL function,
     * and replaced when the SQL is created.
     *
     * @param string               $value
     * @param object               $inputValue
     * @param string               $fieldName
     * @param FieldConfigInterface $field
     * @param integer|null         $strategy
     * @param boolean              $noSqlConversion
     *
     * @return string|float|integer
     */
    private function getValueAsString($value, $inputValue, $fieldName, FieldConfigInterface $field, $strategy = null, $noSqlConversion = false)
    {
        /** @var \Doctrine\DBAL\Types\Type $type */
        $type = $this->fieldsData[$fieldName]['dbType'];

        // No conversions so set the value as query-parameter
        if (!isset($this->valueConversions[$fieldName])) {
            $paramName = $this->getUniqueParameterName($fieldName);
            $this->query->setParameter($paramName, $value, $type);
            $this->parameters[$paramName] = $value;

            return ':' . $paramName;
        }

        $convertedValue = $value;
        $converter = $this->valueConversions[$fieldName];

        $hints = array(
            'searchField' => $field,
            'connection' => $this->entityManager->getConnection(),
            'dbType' => $this->fieldsData[$fieldName]['dbType'],
            'conversionStrategy' => $strategy,

            'originalValue' => $value,
            'valueObject' => $inputValue,
        );

        if ($converter->requiresBaseConversion($value, $field->getOptions(), $hints)) {
            $convertedValue = $type->convertToDatabaseValue($value, $this->entityManager->getConnection()->getDatabasePlatform());
        }

        $convertedValue = $converter->convertValue($convertedValue, $field->getOptions(), $hints);
        if (!$noSqlConversion && $converter instanceof SqlValueConversionInterface) {
            $valueRequiresEmbedding = $converter->valueRequiresEmbedding($value, $field->getOptions(), $hints);

            // If the value requires embedding but DQL is used we inform the DQL function about the parameter-index
            // Where he can then find the value, else its not possible to embed the value safely
            if (!$valueRequiresEmbedding || ($valueRequiresEmbedding && !$this->query instanceof NativeQuery)) {
                $paramName = $this->getUniqueParameterName($fieldName);
                $this->query->setParameter($paramName, $convertedValue);
                $this->parameters[$paramName] = $convertedValue;
                $convertedValue = ':' . $paramName;
            }

            if (!$this->query instanceof NativeQuery) {
                $convertedValue = "RW_SEARCH_VALUE_CONVERSION('$fieldName', " . $this->fieldsData[$fieldName]['column'] . ", $convertedValue, " . (null === $strategy ? 'null' : $strategy) . ", " . ($valueRequiresEmbedding ? 'true' : 'false') . ")";
            } else {
                $convertedValue = $this->getValueConversionSql($fieldName, $this->fieldsData[$fieldName]['column'], $convertedValue, $field, $strategy);
            }

            return $convertedValue;
        }

        $paramName = $this->getUniqueParameterName($fieldName);
        $this->query->setParameter($paramName, $convertedValue);
        $this->parameters[$paramName] = $convertedValue;

        return ':' . $paramName;
    }

    /**
     * @param string $fieldName
     *
     * @return string
     */
    private function getUniqueParameterName($fieldName)
    {
        if (!isset($this->paramPosition[$fieldName])) {
            $this->paramPosition[$fieldName] = -1;
        }

        return (null !== $this->parameterPrefix ? $this->parameterPrefix . '_' : '') . $fieldName . '_' . $this->paramPosition[$fieldName] += 1;
    }

    /**
     * Returns the resolved field column.
     *
     * @param string $entity
     * @param string $column
     * @param string $fieldName
     *
     * @return string
     *
     * @throws InvalidConfigurationException
     */
    private function resolveFieldColumn($entity, $column, $fieldName)
    {
        $metaData = $this->entityManager->getClassMetadata($entity);

        if (isset($this->entityFieldMapping[$fieldName])) {
            $columnPrefix = $this->entityFieldMapping[$fieldName] . '.';
        } else {
            // We cant use the prefix directly as it might be JOIN column
            if (!$this->query instanceof NativeQuery && $metaData->isAssociationWithSingleJoinColumn($column)) {
                $joiningClass = $metaData->getAssociationTargetClass($column);
                if (!isset($this->entityClassMapping[$joiningClass])) {
                    throw new InvalidConfigurationException(sprintf(
                        "No entity-mapping set for \"%s\", used by \"%s\"#%s using a JOIN association.\n
                        You can solve this by either adding the mapping for \"%s\", or by setting the alias for \"%\$1s\" explicitly.",
                        $joiningClass,
                        $entity, $column)
                    );
                }

                $columnPrefix = $this->entityClassMapping[$joiningClass] . '.';
                $column = $metaData->getSingleAssociationReferencedJoinColumnName($column);
            } elseif (isset($this->entityClassMapping[$entity])) {
                $columnPrefix = $this->entityClassMapping[$entity] . '.';
            } else {
                throw new InvalidConfigurationException(sprintf('Unable to determine entity-alias mapping for "%s"#%s, set the entity mapping explicitly.', $entity, $column));
            }
        }

        $column = $columnPrefix . $column;

        return $column;
    }

    /**
     * @param string $entity
     * @param string $propertyName
     *
     * @return ORMType
     *
     * @throws \RuntimeException
     */
    private function getDbType($entity, $propertyName)
    {
        $metaData = $this->entityManager->getClassMetadata($entity);

        if (!($type = $metaData->getTypeOfField($propertyName))) {
            // As there is no type, the only logical part is a JOIN, but we can only process a single Column JOIN
            if (!$metaData->isAssociationWithSingleJoinColumn($propertyName)) {
                throw new \RuntimeException(sprintf('Column "%s"::"%s" seems be to a JOIN but has multiple reference columns, making it impossible to determine the correct type.'));
            }

            $joiningClass = $metaData->getAssociationTargetClass($propertyName);
            $referencedColumnName = $metaData->getSingleAssociationReferencedJoinColumnName($propertyName);
            $type = $this->entityManager->getClassMetadata($joiningClass)->getTypeOfField($referencedColumnName);
        }

        if (!is_object($type)) {
            $type = ORMType::getType($type);
        }

        return $type;
    }

    /**
     * Returns the correct column (with SQLField conversions applied).
     *
     * @param string               $fieldName
     * @param FieldConfigInterface $field
     * @param null|integer         $strategy
     *
     * @return string
     */
    private function getFieldColumn($fieldName, FieldConfigInterface $field, $strategy = null)
    {
        if (isset($this->fieldsMappingCache[$fieldName]) && array_key_exists($strategy, $this->fieldsMappingCache[$fieldName])) {
            return $this->fieldsMappingCache[$fieldName][$strategy];
        }

        $this->fieldsMappingCache[$fieldName][$strategy] = $column = $this->fieldsData[$fieldName]['column'];
        if (isset($this->fieldConversions[$fieldName])) {
            if (!$this->query instanceof NativeQuery) {
                $this->fieldsMappingCache[$fieldName][$strategy] = "RW_SEARCH_FIELD_CONVERSION('$fieldName', $column, " . (null === $strategy ? 'null' : $strategy) . ")";
            } else {
                $this->fieldsMappingCache[$fieldName][$strategy] = $this->getFieldConversionSql($fieldName, $column, $field, $strategy);
            }
        }

        return $this->fieldsMappingCache[$fieldName][$strategy];
    }

    /**
     * Returns the resolved Entity class-name.
     *
     * @param string $entity
     *
     * @return string
     */
    private function resolveEntityClass($entity)
    {
        if (false !== strpos($entity, ':')) {
            return $this->entityManager->getClassMetadata($entity)->name;
        }

        return ClassUtils::getRealClass($entity);
    }
}
