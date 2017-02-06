<?php

declare(strict_types=1);

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
use Rollerworks\Component\Search\SearchCondition;

/**
 * SearchCondition Doctrine DBAL WhereBuilder.
 *
 * This class provides the functionality for creating an SQL WHERE-clause
 * based on the provided SearchCondition.
 *
 * Note that only fields that have been configured with `setField()`
 * will be actually used in the generated query.
 *
 * Keep the following in mind when using conversions.
 *
 *  * Conversions are performed per search field and must be stateless,
 *    they receive the db-type and connection information for the conversion process.
 *  * Conversions apply at the SQL level, meaning they must be platform specific.
 *  * SQL conversions must be properly escaped to prevent SQL injections.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class WhereBuilder implements WhereBuilderInterface
{
    /**
     * @var SearchCondition
     */
    private $searchCondition;

    /**
     * @var FieldSet
     */
    private $fieldSet;

    /**
     * @var array
     */
    private $converters = [];

    /**
     * @var string
     */
    private $whereClause;

    /**
     * @var array[]
     */
    private $fields = [];

    /**
     * @var Connection
     */
    private $connection;

    /**
     * Constructor.
     *
     * @param Connection      $connection      Doctrine DBAL Connection object
     * @param SearchCondition $searchCondition SearchCondition object
     *
     * @throws BadMethodCallException When SearchCondition contains errors
     */
    public function __construct(Connection $connection, SearchCondition $searchCondition)
    {
        $this->searchCondition = $searchCondition;
        $this->fieldSet = $searchCondition->getFieldSet();
        $this->connection = $connection;
    }

    /**
     * Set the search field to database table-column mapping configuration.
     *
     * To map a field to more then one column use `field-name#mapping-name`
     * for the $fieldName argument. The `field-name` is the field name as registered
     * in the FieldSet, `mapping-name` allows to configure then one mapping for a field.
     *
     * Caution: A field can only have multiple mappings or one, omitting `#` will remove
     * any existing mappings for that field. Registering the field without `#` first and then
     * setting multiple mappings for that field will reset the single mapping.
     *
     * Tip: The `mapping-name` doesn't have to be same as $column, but using a clear name
     * will help with trouble shooting.
     *
     * @param string $fieldName Name of the search field as registered
     *                          in the FieldSet or `field-name#mapping-name`
     *                          to configure a secondary mapping
     * @param string $column    Database table column-name
     * @param string $alias     Table alias as used in the query "u" for
     *                          `FROM users AS u`
     * @param string $type      Doctrine DBAL registered type
     *
     * @return WhereBuilder When the field is not registered in the fieldset
     */
    public function setField(string $fieldName, string $column, string $alias = null, string $type = 'string')
    {
        if ($this->whereClause) {
            throw new BadMethodCallException(
                'WhereBuilder configuration methods cannot be accessed anymore once the where-clause is generated.'
            );
        }

        $mappingIdx = null;

        if (false !== strpos($fieldName, '#')) {
            list($fieldName, $mappingIdx) = explode('#', $fieldName, 2);
            unset($this->fields[$fieldName][null]);
        } else {
            $this->fields[$fieldName] = [];
        }

        $this->fields[$fieldName][$mappingIdx] = [];
        $this->fields[$fieldName][$mappingIdx]['field'] = $this->fieldSet->get($fieldName);
        $this->fields[$fieldName][$mappingIdx]['db_type'] = MappingType::getType($type);
        $this->fields[$fieldName][$mappingIdx]['alias'] = $alias;
        $this->fields[$fieldName][$mappingIdx]['column'] = $column;
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
    public function setConverter(string $fieldName, $converter)
    {
        if ($this->whereClause) {
            throw new BadMethodCallException('WhereBuilder configuration methods cannot be accessed anymore once the where-clause is generated.');
        }

        if (!$this->fieldSet->has($fieldName)) {
            throw new UnknownFieldException($fieldName);
        }

        $this->converters[$fieldName] = $converter;

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
    public function getWhereClause(string $prependQuery = ''): string
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
     * @return SearchCondition
     */
    public function getSearchCondition(): SearchCondition
    {
        return $this->searchCondition;
    }

    private function processFields()
    {
        $fields = [];

        foreach ($this->fields as $fieldName => $fieldMappings) {
            foreach ($fieldMappings as $n => $field) {
                $fields[$fieldName][$n] = new QueryField(
                    $fieldName.(null !== $n ? "#$n" : ''),
                    $field['field'],
                    $field['db_type'],
                    $field['column'],
                    $field['alias'],
                    $this->converters[$fieldName] ?? null
                );
            }
        }

        return $fields;
    }

    private function getQueryPlatform(array $fields): QueryPlatformInterface
    {
        $dbPlatform = ucfirst($this->connection->getDatabasePlatform()->getName());
        $platformClass = 'Rollerworks\\Component\\Search\\Doctrine\\Dbal\\QueryPlatform\\'.$dbPlatform.'QueryPlatform';

        if (class_exists($platformClass)) {
            return new $platformClass($this->connection, $fields);
        }

        throw new \RuntimeException(sprintf('No supported class found for database-platform "%s".', $dbPlatform));
    }
}
