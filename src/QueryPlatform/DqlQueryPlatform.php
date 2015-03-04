<?php

/*
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Doctrine\Orm\QueryPlatform;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Rollerworks\Component\Search\Doctrine\Dbal\Query\QueryField;
use Rollerworks\Component\Search\Doctrine\Dbal\QueryPlatform\AbstractQueryPlatform;
use Rollerworks\Component\Search\Doctrine\Dbal\SqlFieldConversionInterface;
use Rollerworks\Component\Search\Doctrine\Dbal\SqlValueConversionInterface;

final class DqlQueryPlatform extends AbstractQueryPlatform
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var array
     */
    private $embeddedValues = [];

    /**
     * @var int
     */
    private $valuesIndex = 0;

    /**
     * Constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param QueryField[]           $fields
     */
    public function __construct(EntityManagerInterface $entityManager, array $fields)
    {
        parent::__construct($entityManager->getConnection(), $fields);

        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getFieldColumn($fieldName, $strategy = 0, $column = '')
    {
        if (isset($this->fieldsMappingCache[$fieldName][$strategy])) {
            return $this->fieldsMappingCache[$fieldName][$strategy];
        }

        $field = $this->fields[$fieldName];
        $column = $field->getColumn();

        $this->fieldsMappingCache[$fieldName][$strategy] = $column;

        if ($field->getFieldConversion() instanceof SqlFieldConversionInterface) {
            $this->fieldsMappingCache[$fieldName][$strategy] = sprintf(
                "RW_SEARCH_FIELD_CONVERSION('%s', %s, %s)",
                $fieldName,
                $column,
                (int) $strategy
            );
        }

        return $this->fieldsMappingCache[$fieldName][$strategy];
    }

    /**
     * @return mixed[]
     */
    public function getEmbeddedValues()
    {
        return $this->embeddedValues;
    }

    /**
     * {@inheritdoc}
     */
    protected function convertValue($value, $fieldName, $column, $strategy = 0)
    {
        $field = $this->fields[$fieldName];
        $converter = $field->getValueConversion();
        $options = $field->getFieldConfig()->getOptions();
        $type = $field->getDbType();

        $convertedValue = $value;
        $hints = $this->getConversionHints($fieldName, $column, $strategy);

        if ($converter->requiresBaseConversion($value, $options, $hints)) {
            $convertedValue = $type->convertToDatabaseValue($value, $this->connection->getDatabasePlatform());
        }

        $convertedValue = $converter->convertValue($convertedValue, $options, $hints);

        if ($converter instanceof SqlValueConversionInterface) {
            $this->embeddedValues[++$this->valuesIndex] = $convertedValue;

            return sprintf(
                "RW_SEARCH_VALUE_CONVERSION('%s', %s, %s, %s)",
                $fieldName,
                $column,
                $this->valuesIndex,
                (int) $strategy
            );
        }

        return $this->quoteValue($convertedValue, $type);
    }

    /**
     * {@inheritdoc}
     */
    protected function quoteValue($value, Type $type)
    {
        if (is_numeric($value) && !is_string($value)) {
            return (string) $value;
        } elseif (is_bool($value)) {
            return $value ? "true" : "false";
        } else {
            return "'".str_replace("'", "''", $value)."'";
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getMatchSqlRegex($column, $value, $caseInsensitive, $negative)
    {
        return sprintf(
            "RW_SEARCH_MATCH(%s, %s, %s) %s 1",
            $column,
            $value,
            $this->quoteValue($caseInsensitive, Type::getType(Type::BOOLEAN)),
            ($negative ? '<>' : '=')
        );
    }
}
