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

use Doctrine\DBAL\Types\Type as MappingType;
use Doctrine\ORM\EntityManagerInterface;
use Rollerworks\Component\Search\Doctrine\Dbal\Query\QueryField;
use Rollerworks\Component\Search\FieldSet;

/**
 * @internal
 */
final class FieldConfigBuilder
{
    private $fieldSet;
    private $native;
    private $entityManager;

    private $fields = [];
    private $defaultEntity;
    private $defaultAlias;

    public function __construct(EntityManagerInterface $entityManager, FieldSet $fieldSet, bool $native = false)
    {
        $this->entityManager = $entityManager;
        $this->fieldSet = $fieldSet;
        $this->native = $native;
    }

    public function setDefaultEntity(string $entity, string $alias)
    {
        $this->defaultEntity = $this->entityManager->getClassMetadata($entity)->getName();
        $this->defaultAlias = $alias;
    }

    public function setField(string $mappingName, string $property, string $alias = null, string $entity = null, string $type = null)
    {
        $mappingIdx = null;
        $fieldName = $mappingName;

        if (false !== strpos($mappingName, '#')) {
            list($fieldName, $mappingIdx) = explode('#', $mappingName, 2);
            unset($this->fields[$fieldName][null]);
        } else {
            $this->fields[$fieldName] = [];
        }

        list($entity, $property) = $this->getEntityAndProperty(
            $mappingName,
            $entity ?? $this->defaultEntity,
            $property
        );

        $this->fields[$fieldName][$mappingIdx] = new QueryField(
            $mappingName,
            $this->fieldSet->get($fieldName),
            $this->getMappingType($mappingName, $entity, $property, $type),
            $this->native ? $this->entityManager->getClassMetadata($entity)->getColumnName($property) : $property,
            $alias ?? $this->defaultAlias
        );
    }

    public function getFields(): array
    {
        return $this->fields;
    }

    public function getFieldsForHint(): array
    {
        $resolvedFields = [];

        foreach ($this->fields as $fieldName => $mappings) {
            foreach ($mappings as $fieldConfig) {
                $resolvedFields[$fieldConfig->mappingName] = $fieldConfig;
            }
        }

        return $resolvedFields;
    }

    private function getEntityAndProperty($fieldName, string $entity, string $property): array
    {
        $metadata = $this->entityManager->getClassMetadata($entity);

        if (!$metadata->hasAssociation($property)) {
            return [$entity, $property];
        }

        // Referencing a JOIN column is only possible for native, and only when it's a
        // SingleJoinColumn.
        if ($this->native && $metadata->isAssociationWithSingleJoinColumn($property)) {
            return [$entity, $property];
        }

        throw new \RuntimeException(
            sprintf(
                'Entity field "%s"#%s is a JOIN association, you must explicitly set the '.
                'entity alias and column mapping for search field "%s" to point to the (head) reference and the '.
                'entity field you want to use, this entity field must be owned by the entity '.
                '(not reference another entity). If the entity field is used in a many-to-many relation you must '.
                'reference the targetEntity that is set on the ManyToMany mapping and use the entity field of '.
                'that entity.',
                $entity,
                $property,
                $fieldName
            )
        );
    }

    private function getMappingType(string $fieldName, string $entity, string $propertyName, string $type = null): MappingType
    {
        if (!$type) {
            $type = $this->entityManager->getClassMetadata($entity)->getTypeOfField($propertyName);
        }

        if (null === $type) {
            throw new \RuntimeException(
                sprintf(
                    'Unable to determine DBAL type of field-mapping "%s" with entity reference "%s"#%s. '.
                    'Configure an explicit dbal type for the field.',
                    $fieldName,
                    $entity,
                    $propertyName
                )
            );
        }

        return is_object($type) ? $type : MappingType::getType($type);
    }
}
