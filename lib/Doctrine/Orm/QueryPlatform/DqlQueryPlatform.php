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

namespace Rollerworks\Component\Search\Doctrine\Orm\QueryPlatform;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManagerInterface;
use Rollerworks\Component\Search\Doctrine\Dbal\ColumnConversion;
use Rollerworks\Component\Search\Doctrine\Dbal\Query\QueryField;
use Rollerworks\Component\Search\Doctrine\Dbal\QueryPlatform\AbstractQueryPlatform;
use Rollerworks\Component\Search\Doctrine\Dbal\ValueConversion;

final class DqlQueryPlatform extends AbstractQueryPlatform
{
    /**
     * @var array
     */
    private $embeddedValues = [];

    /**
     * @var int
     */
    private $currentEmbeddedValuesIndex = 0;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager->getConnection());
    }

    public function getFieldColumn(QueryField $mappingConfig, int $strategy = 0, string $column = null): string
    {
        $mappingName = $mappingConfig->mappingName;

        if (isset($this->fieldsMappingCache[$mappingName][$strategy])) {
            return $this->fieldsMappingCache[$mappingName][$strategy];
        }

        if (null === $column) {
            $column = $mappingConfig->column;
        }

        $this->fieldsMappingCache[$mappingName][$strategy] = $column;

        if ($mappingConfig->columnConversion instanceof ColumnConversion) {
            $this->fieldsMappingCache[$mappingName][$strategy] = sprintf(
                "RW_SEARCH_FIELD_CONVERSION('%s', %s, %d)",
                $mappingName,
                $column,
                $strategy
            );
        }

        return $this->fieldsMappingCache[$mappingName][$strategy];
    }

    /**
     * @return mixed[]
     *
     * @internal
     */
    public function getEmbeddedValues(): array
    {
        return $this->embeddedValues;
    }

    public function getValueAsSql($value, QueryField $mappingConfig, string $column, int $strategy = 0): string
    {
        if ($mappingConfig->valueConversion instanceof ValueConversion) {
            $this->embeddedValues[++$this->currentEmbeddedValuesIndex] = $value;

            return sprintf(
                "RW_SEARCH_VALUE_CONVERSION('%s', %s, %s, %s)",
                $mappingConfig->mappingName,
                $column,
                $this->currentEmbeddedValuesIndex,
                $strategy
            );
        }

        return $this->quoteValue(
            $mappingConfig->dbType->convertToDatabaseValue($value, $this->connection->getDatabasePlatform()),
            $mappingConfig->dbType
        );
    }

    protected function quoteValue($value, Type $type): string
    {
        if (\is_bool($value)) {
            return $value ? 'true' : 'false';
        } elseif (is_scalar($value) && ctype_digit((string) $value)) {
            return (string) $value;
        }

        return "'".str_replace("'", "''", $value)."'";
    }
}
