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
use Rollerworks\Component\Search\Doctrine\Dbal\AbstractWhereBuilder;
use Rollerworks\Component\Search\Doctrine\Dbal\ConversionStrategyInterface;
use Rollerworks\Component\Search\Doctrine\Dbal\QueryGenerator;
use Rollerworks\Component\Search\Doctrine\Dbal\SqlFieldConversionInterface;
use Rollerworks\Component\Search\Doctrine\Dbal\ValueConversionInterface;
use Rollerworks\Component\Search\Exception\BadMethodCallException;
use Rollerworks\Component\Search\Exception\InvalidConfigurationException;
use Rollerworks\Component\Search\Exception\UnexpectedTypeException;
use Rollerworks\Component\Search\Exception\UnknownFieldException;
use Rollerworks\Component\Search\FieldConfigInterface;
use Rollerworks\Component\Search\SearchConditionInterface;

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
class WhereBuilder extends AbstractWhereBuilder implements WhereBuilderInterface
{
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
     * @throws UnknownFieldException  When the field is not registered in the fieldset.
     * @throws BadMethodCallException When the where-clause is already generated.
     */
    public function setFieldMapping($fieldName, $alias)
    {
        if ($this->whereClause) {
            throw new BadMethodCallException('WhereBuilder configuration methods cannot be accessed anymore once the where-clause is generated.');
        }

        if (!$this->searchCondition->getFieldSet()->has($fieldName)) {
            throw new UnknownFieldException($fieldName);
        }

        if (null === $alias) {
            unset($this->entityFieldMapping[$fieldName]);
        } else {
            $this->entityFieldMapping[$fieldName] = $alias;
        }

        return $this;
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
     * @param boolean $embedValues Whether to embed the values (NativeQuery only), default is to assign as parameters.
     *
     * @return string
     */
    public function getWhereClause($embedValues = false)
    {
        if (null !== $this->whereClause) {
            return $this->whereClause;
        }

        $this->processEntityMappings();
        $this->processFields();

        if ($this->query instanceof NativeQuery) {
            $this->queryGenerator = new QueryGenerator($this->entityManager->getConnection(), $this->searchCondition, $this->fields, $this->parameterPrefix, $embedValues);
        } else {
            $this->queryGenerator = new DqlQueryGenerator($this->entityManager->getConnection(), $this->searchCondition, $this->fields, $this->parameterPrefix);
        }

        $this->whereClause = $this->queryGenerator->getGroupQuery($this->searchCondition->getValuesGroup());
        foreach ($this->queryGenerator->getParameters() as $paramName => $paramValue) {
            $this->query->setParameter($paramName, $paramValue);
        }

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
            $this->query->setSQL($this->query->getSQL().$prependQuery.$whereCase);
        } else {
            $this->query->setDQL($this->query->getDQL().$prependQuery.$whereCase);
        }

        if ($this->query instanceof DqlQuery) {
            $this->query->setHint($this->getQueryHintName(), $this->getQueryHintValue());
        }

        $this->queryModified = true;

        return $this;
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
        return function () use (&$self) {
            return $self;
        };
    }

    /**
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->entityManager;
    }

    /**
     * @return NativeQuery|DqlQuery|QueryBuilder
     */
    public function getQuery()
    {
        return $this->query;
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
     *
     * @throws BadMethodCallException when there is no QueryGenerator
     */
    public function getFieldConversionSql($fieldName, $column, FieldConfigInterface $field = null, $strategy = null)
    {
        $field = $field ? : $this->fieldset->get($fieldName);

        if ($this->queryGenerator) {
            return $this->queryGenerator->getFieldConversionSql($fieldName, $column, $field, $strategy);
        }

        throw new BadMethodCallException('getFieldConversionSql() is meant for internal usage, you should not call it manually.');
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
     * @throws BadMethodCallException when there is no QueryGenerator
     */
    public function getValueConversionSql($fieldName, $column, $value, FieldConfigInterface $field = null, $strategy = null, $isValueEmbedded = false)
    {
        $field = $field ? : $this->fieldset->get($fieldName);

        if ($this->queryGenerator) {
            return $this->queryGenerator->getValueConversionSql($fieldName, $column, $value, $field, $strategy, $isValueEmbedded);
        }

        throw new BadMethodCallException('getValueConversionSql() is meant for internal usage, you should not call it manually.');
    }

    private function processEntityMappings()
    {
        // Resolve the EntityClassMappings to a real class-name.
        foreach ($this->entityClassMapping as $class => $alias) {
            if (false !== strpos($class, ':')) {
                $realClass = $this->entityManager->getClassMetadata($class)->name;
            } else {
                $realClass = ClassUtils::getRealClass($class);
            }

            if ($realClass !== $class) {
                $this->entityClassMapping[$realClass] = $alias;
                unset($this->entityClassMapping[$class]);
            }
        }
    }

    private function processFields()
    {
        // Initialize the information for the fields.
        foreach ($this->fieldset->all() as $fieldName => $fieldConfig) {
            $field = $this->fieldset->get($fieldName);
            if (null === $field->getModelRefClass()) {
                continue;
            }

            $this->fields[$fieldName] = array();
            $this->fields[$fieldName]['db_type'] = $this->getDbType($field->getModelRefClass(), $field->getModelRefProperty());
            $this->fields[$fieldName]['column'] = $this->resolveFieldColumn($field->getModelRefClass(), $field->getModelRefProperty(), $fieldName);
            $this->fields[$fieldName]['field'] = $fieldConfig;
            $this->fields[$fieldName]['field_convertor'] = isset($this->fieldConversions[$fieldName]) ? $this->fieldConversions[$fieldName] : null;
            $this->fields[$fieldName]['value_convertor'] = isset($this->valueConversions[$fieldName]) ? $this->valueConversions[$fieldName] : null;
        }
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
            $columnPrefix = $this->entityFieldMapping[$fieldName].'.';
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

                $columnPrefix = $this->entityClassMapping[$joiningClass].'.';
                $column = $metaData->getSingleAssociationReferencedJoinColumnName($column);
            } elseif (isset($this->entityClassMapping[$entity])) {
                $columnPrefix = $this->entityClassMapping[$entity].'.';
            } else {
                throw new InvalidConfigurationException(sprintf('Unable to determine entity-alias mapping for "%s"#%s, set the entity mapping explicitly.', $entity, $column));
            }
        }

        $column = $columnPrefix.$column;

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
}
