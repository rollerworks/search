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
 * Handles abstracted handling of the Doctrine WhereBuilder.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
abstract class AbstractWhereBuilder implements ConfigurableWhereBuilderInterface
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

    /**
     * Constructor.
     *
     * @param SearchCondition        $searchCondition SearchCondition object
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(SearchCondition $searchCondition, EntityManagerInterface $entityManager)
    {
        $this->searchCondition = $searchCondition;
        $this->fieldset = $searchCondition->getFieldSet();

        $this->entityManager = $entityManager;
        $this->fieldsConfig = new FieldConfigBuilder($entityManager, $this->fieldset);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultEntity(string $entity, string $alias)
    {
        $this->guardNotGenerated();
        $this->fieldsConfig->setDefaultEntity($entity, $alias);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setField(string $fieldName, string $property, string $alias = null, string $entity = null, string $dbType = null)
    {
        $this->guardNotGenerated();
        $this->fieldsConfig->setField($fieldName, $property, $alias, $entity, $dbType);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchCondition()
    {
        return $this->searchCondition;
    }

    /**
     * @return EntityManagerInterface
     */
    public function getEntityManager()
    {
        return $this->entityManager;
    }

    /**
     * @return FieldConfigBuilder
     *
     * @internal
     */
    public function getFieldsConfig()
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
                'WhereBuilder configuration methods cannot be accessed anymore once the where-clause is generated.'
            );
        }
    }
}
