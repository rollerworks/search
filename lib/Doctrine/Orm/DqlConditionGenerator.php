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

namespace Rollerworks\Component\Search\Doctrine\Orm;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Rollerworks\Component\Search\Doctrine\Dbal\Query\QueryGenerator;
use Rollerworks\Component\Search\Doctrine\Orm\QueryPlatform\DqlQueryPlatform;
use Rollerworks\Component\Search\Exception\BadMethodCallException;
use Rollerworks\Component\Search\SearchCondition;
use Rollerworks\Component\Search\SearchOrder;

/**
 * SearchCondition Doctrine ORM DQL ConditionGenerator.
 *
 * This class provides the functionality for creating a DQL
 * WHERE-clause based on the provided SearchCondition.
 *
 * Note: This class should not be used directly, use the provided ConditionGenerators instead.
 */
final class DqlConditionGenerator
{
    /**
     * @var SearchCondition
     */
    private $searchCondition;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var string
     */
    private $whereClause;

    /**
     * @var FieldConfigBuilder
     */
    private $fieldsConfig;

    /**
     * @var ArrayCollection
     */
    private $parameters;

    public function __construct(EntityManagerInterface $entityManager, SearchCondition $searchCondition, FieldConfigBuilder $configBuilder)
    {
        $this->entityManager = $entityManager;
        $this->searchCondition = $searchCondition;
        $this->fieldsConfig = $configBuilder;
    }

    public function getWhereClause(): string
    {
        $fields = $this->fieldsConfig->getFields();
        $connection = $this->entityManager->getConnection();
        $platform = new DqlQueryPlatform($connection);
        $queryGenerator = new QueryGenerator($connection, $platform, $fields);

        $this->whereClause = $queryGenerator->getWhereClause($this->searchCondition);
        $this->parameters = $platform->getParameters();

        return $this->whereClause;
    }

    public function getParameters(): ArrayCollection
    {
        if (! isset($this->parameters)) {
            throw new BadMethodCallException('getParameters() cannot be called before getWhereClause()');
        }

        return $this->parameters;
    }

    public static function applySortingTo(?SearchOrder $order, QueryBuilder $qb, FieldConfigBuilder $configBuilder): void
    {
        if ($order === null) {
            return;
        }

        $fields = $configBuilder->getFields();

        foreach ($order->getFields() as $fieldName => $direction) {
            if (! isset($fields[$fieldName])) {
                continue;
            }

            if (\count($fields[$fieldName]) > 1) {
                throw new BadMethodCallException(\sprintf('Field "%s" is registered as multiple mapping and cannot be used for sorting.', $fieldName));
            }

            $qb->addOrderBy($fields[$fieldName][null]->column, \strtoupper($direction));
        }
    }
}
