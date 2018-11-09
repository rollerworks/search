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

use Doctrine\ORM\EntityManagerInterface;
use Rollerworks\Component\Search\Exception\BadMethodCallException;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\SearchCondition;

/**
 * Handles abstracted logic of a Doctrine ORM ConditionGenerator.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * @internal this class should not be relied upon, use the ConditionGenerator
 *           interface instead for type hinting
 */
abstract class AbstractConditionGenerator implements ConditionGenerator
{
    use QueryPlatformTrait;

    /**
     * @var SearchCondition
     */
    protected $searchCondition;

    /**
     * @var FieldSet
     */
    protected $fieldset;

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var string
     */
    protected $whereClause;

    /**
     * @var FieldConfigBuilder
     */
    protected $fieldsConfig;

    public function __construct(SearchCondition $searchCondition, EntityManagerInterface $entityManager)
    {
        $this->searchCondition = $searchCondition;
        $this->fieldset = $searchCondition->getFieldSet();

        $this->entityManager = $entityManager;
        $this->fieldsConfig = new FieldConfigBuilder($entityManager, $this->fieldset);
    }

    public function setDefaultEntity(string $entity, string $alias)
    {
        $this->guardNotGenerated();
        $this->fieldsConfig->setDefaultEntity($entity, $alias);

        return $this;
    }

    public function setField(string $fieldName, string $property, string $alias = null, string $entity = null, string $dbType = null)
    {
        $this->guardNotGenerated();
        $this->fieldsConfig->setField($fieldName, $property, $alias, $entity, $dbType);

        return $this;
    }

    /**
     * @internal
     */
    public function getSearchCondition(): SearchCondition
    {
        return $this->searchCondition;
    }

    /**
     * @internal
     */
    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    /**
     * @internal
     */
    public function getFieldsConfig(): FieldConfigBuilder
    {
        return $this->fieldsConfig;
    }

    /**
     * @throws BadMethodCallException When the where-clause is already generated
     */
    protected function guardNotGenerated()
    {
        if (null !== $this->whereClause) {
            throw new BadMethodCallException(
                'ConditionGenerator configuration methods cannot be accessed anymore once the where-clause is generated.'
            );
        }
    }
}
