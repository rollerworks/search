<?php

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
use Rollerworks\Component\Search\SearchConditionInterface;

/**
 * Handles abstracted handling of the Doctrine WhereBuilder.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
abstract class AbstractWhereBuilder implements ConfigurableWhereBuilderInterface
{
    use QueryPlatformTrait;

    /**
     * @var SearchConditionInterface
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
     * @param SearchConditionInterface $searchCondition SearchCondition object
     * @param EntityManagerInterface   $entityManager
     */
    public function __construct(SearchConditionInterface $searchCondition, EntityManagerInterface $entityManager)
    {
        if ($searchCondition->getValuesGroup()->hasErrors(true)) {
            throw new BadMethodCallException(
                'Unable to generate the where-clause, because the SearchCondition contains errors.'
            );
        }

        $this->searchCondition = $searchCondition;
        $this->fieldset = $searchCondition->getFieldSet();

        $this->entityManager = $entityManager;
        $this->fieldsConfig = new FieldConfigBuilder($entityManager, $this->fieldset);
    }

    /**
     * {@inheritdoc}
     *
     * @throws BadMethodCallException When the where-clause is already generated.
     *
     * @return self
     */
    public function setEntityMapping($entityName, $alias)
    {
        $this->guardNotGenerated();
        $this->fieldsConfig->setEntityMapping($entityName, $alias);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @throws BadMethodCallException When the where-clause is already generated.
     *
     * @return self
     */
    public function setEntityMappings(array $mapping)
    {
        $this->guardNotGenerated();
        $this->fieldsConfig->setEntityMappings($mapping);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @throws BadMethodCallException When the where-clause is already generated.
     *
     * @return self
     */
    public function setField($fieldName, $alias, $entity = null, $property = null, $mappingType = null)
    {
        $this->guardNotGenerated();
        $this->fieldsConfig->setField($fieldName, $alias, $entity, $property, $mappingType);

        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @throws BadMethodCallException When the where-clause is already generated.
     *
     * @return self
     */
    public function setConverter($fieldName, $converter)
    {
        $this->guardNotGenerated();
        $this->fieldsConfig->setConverter($fieldName, $converter);

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
     * @throws BadMethodCallException When the where-clause is already generated.
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
