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

use Doctrine\DBAL\Types\Type as MappingType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Rollerworks\Component\Search\Doctrine\Dbal\Query\QueryField;
use Rollerworks\Component\Search\Doctrine\Dbal\SqlFieldConversionInterface;
use Rollerworks\Component\Search\Doctrine\Dbal\ValueConversionInterface;
use Rollerworks\Component\Search\Exception\UnknownFieldException;
use Rollerworks\Component\Search\FieldConfigInterface;
use Rollerworks\Component\Search\FieldSet;

/**
 * @internal
 */
final class FieldConfigBuilder
{
    private $fieldSet;
    private $entityManager;
    private $valueConversions = [];
    private $fieldConversions = [];
    private $entityClassMapping = [];
    private $entityFieldMapping = [];
    private $combinedFields = [];

    public function __construct(EntityManagerInterface $entityManager, FieldSet $fieldSet)
    {
        $this->entityManager = $entityManager;
        $this->fieldSet = $fieldSet;
    }

    public function setEntityMapping($entity, $alias)
    {
        $this->entityClassMapping[$entity] = $alias;
    }

    public function setEntityMappings(array $mapping)
    {
        $this->entityClassMapping = $mapping;
    }

    public function setField($fieldName, $alias, $entity = null, $property = null, $type = null)
    {
        $fieldConfig = $this->fieldSet->get($fieldName);

        $this->entityFieldMapping[$fieldName] = [];
        $this->entityFieldMapping[$fieldName]['alias'] = $alias;
        $this->entityFieldMapping[$fieldName]['entity'] = $entity ?: $fieldConfig->getModelRefClass();
        $this->entityFieldMapping[$fieldName]['property'] = $property ?: $fieldConfig->getModelRefProperty();
        $this->entityFieldMapping[$fieldName]['mapping_type'] = $type;

        if (null === $this->entityFieldMapping[$fieldName]['entity'] &&
            null === $this->entityFieldMapping[$fieldName]['property']
        ) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Search field "%s" has no model mapping configured and no (complete) entity mapping is given. '.
                    'Make sure the field has a model mapping configured or set the $entity and $property parameters '.
                    'to set the entity mapping explicitly.',
                    $fieldName
                )
            );
        }

        unset($this->combinedFields[$fieldName]);
    }

    public function setCombinedField($fieldName, array $mappings)
    {
        $fieldConfig = $this->fieldSet->get($fieldName);

        foreach ($mappings as $n => $mapping) {
            if (!isset($mapping['property'])) {
                throw new \InvalidArgumentException(
                    sprintf('Combined search field "%s" is missing "property" at index "%s".', $fieldName, $n)
                );
            }

            $this->combinedFields[$fieldName][$n] = [];
            $this->combinedFields[$fieldName][$n]['field'] = $fieldConfig;
            $this->combinedFields[$fieldName][$n]['alias'] = isset($mapping['alias']) ? $mapping['alias'] : null;
            $this->combinedFields[$fieldName][$n]['entity'] = isset($mapping['class']) ? $mapping['class'] : $fieldConfig->getModelRefClass();
            $this->combinedFields[$fieldName][$n]['property'] = $mapping['property'];

            // Do type resolving here to simplify the fields resolving logic.
            $this->combinedFields[$fieldName][$n]['mapping_type'] = isset($mapping['type']) ?
                (is_object($mapping['type']) ? $mapping['type'] : MappingType::getType($mapping['type'])) : null;

            if (null === $this->combinedFields[$fieldName][$n]['entity']) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Combined search field "%s" has no entity set at index "%s" and no entity was set for the field.'.
                        'Make sure the field has a entity mapping configured or set the "model_class" option for the field.'.
                        $fieldName,
                        $n
                    )
                );
            }
        }

        unset($this->entityFieldMapping[$fieldName]);
    }

    public function setConverter($fieldName, $converter)
    {
        if (!$this->fieldSet->has($fieldName)) {
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
     * @param bool $useNative
     *
     * @return QueryField[]
     */
    public function getFields($useNative = false)
    {
        $fields = [];

        $this->resolveEntityClasses();

        foreach ($this->fieldSet->all() as $fieldName => $fieldConfig) {
            $entityName = $this->resolveEntity($fieldName, $fieldConfig);

            if (null === $entityName) {
                continue;
            }

            list($entityName, $property) = $this->resolveEntityReference(
                $entityName,
                $fieldConfig->getModelRefProperty(),
                $fieldName
            );

            $fields[$fieldName] = new QueryField(
                $fieldConfig,
                $this->getMappingType($fieldName, $entityName, $property),
                $this->getEntityAlias($fieldName, $entityName),
                $this->getFieldColumn($entityName, $property, $fieldName, $useNative),
                isset($this->fieldConversions[$fieldName]) ? $this->fieldConversions[$fieldName] : null,
                isset($this->valueConversions[$fieldName]) ? $this->valueConversions[$fieldName] : null
            );
        }

        foreach ($this->combinedFields as $fieldName => $subFieldsConfig) {
            $subsFieldNames = [];

            foreach ($subFieldsConfig as $n => $mapping) {
                $fieldNameN = $fieldName.'#'.$n;

                list($entityName, $property) = $this->resolveEntityReference(
                    $mapping['entity'],
                    $mapping['property'],
                    $fieldNameN
                );

                $subsFieldNames[] = $fieldNameN;
                $fields[$fieldNameN] = new QueryField(
                    $mapping['field'],
                    isset($mapping['mapping_type']) ? $mapping['mapping_type'] : $this->getMappingType($fieldName.'#'.$n, $entityName, $property),
                    $mapping['alias'] ?: $this->getEntityAlias($fieldNameN, $entityName),
                    $this->getFieldColumn($entityName, $property, $fieldNameN, $useNative),
                    isset($this->fieldConversions[$fieldName]) ? $this->fieldConversions[$fieldName] : null,
                    isset($this->valueConversions[$fieldName]) ? $this->valueConversions[$fieldName] : null
                );
            }

            $fields[$fieldName] = $subsFieldNames;
        }

        return $fields;
    }

    private function resolveEntityClasses()
    {
        $entities = [];

        foreach ($this->entityClassMapping as $entity => $alias) {
            $class = $this->entityManager->getClassMetadata($entity)->getName();
            $entities[$class] = $alias;
        }

        $this->entityClassMapping = $entities;
    }

    private function resolveEntity($fieldName, FieldConfigInterface $fieldConfig)
    {
        if (isset($this->entityFieldMapping[$fieldName])) {
            $entityName = $this->entityFieldMapping[$fieldName]['entity'];
        } else {
            $entityName = $fieldConfig->getModelRefClass();
        }

        // Skip if the field is has no model-mapping.
        if (null === $entityName) {
            return;
        }

        $entityName = $this->entityManager->getClassMetadata($entityName)->getName();

        // Skip if the entity has no registered alias
        // and no alias is set for the field explicitly
        if (!isset($this->entityClassMapping[$entityName]) && !isset($this->entityFieldMapping[$fieldName])) {
            return;
        }

        return $entityName;
    }

    private function resolveEntityReference($entity, $property, $fieldName)
    {
        if (isset($this->entityFieldMapping[$fieldName]['property'])) {
            /** @var ClassMetadata $metaData */
            $metaData = $this->entityManager->getClassMetadata($this->entityFieldMapping[$fieldName]['entity']);

            if ($metaData->hasAssociation($this->entityFieldMapping[$fieldName]['property'])) {
                throw new \RuntimeException(
                    sprintf(
                        'Search field "%s" is explicitly mapped to "%s"#%s, but the configured entity-mapping '.
                        'refers to a JOIN association. You must set the entity-mapping for search field "%1$s", to '.
                        'the own entity and property.',
                        $fieldName,
                        $entity,
                        $this->entityFieldMapping[$fieldName]['property']
                    )
                );
            }

            return [$this->entityFieldMapping[$fieldName]['entity'], $this->entityFieldMapping[$fieldName]['property']];
        }

        /** @var ClassMetadata $metaData */
        $metaData = $this->entityManager->getClassMetadata($entity);

        // No JOIN association so no need to resolve
        if (!$metaData->hasAssociation($property)) {
            return [$entity, $property];
        }

        if ($metaData->isAssociationWithSingleJoinColumn($property)) {
            $entity = $metaData->getAssociationTargetClass($property);
            $property = $metaData->getSingleAssociationReferencedJoinColumnName($property);

            /** @var \Doctrine\ORM\Mapping\ClassMetadata $joiningClassMeta */
            $joiningClassMeta = $this->entityManager->getClassMetadata($entity);
            $property = $joiningClassMeta->getFieldForColumn($property);

            return [$entity, $property];
        }

        throw new \RuntimeException(
            sprintf(
                'Entity field "%s"#%s is a JOIN association with multiple columns, you must explicitly set the '.
                'entity alias and column mapping for search field "%s" to point to the (head) referenced and the '.
                'entity field you want to use, this entity field must be owned by the entity '.
                '(not reference another entity). If the entity field is used in a many-to-many relation you must '.
                'to reference the targetEntity that is set on the ManyToMany mapping and use the entity field of '.
                'that entity.',
                $entity,
                $property,
                $fieldName
            )
        );
    }

    private function getFieldColumn($entity, $property, $fieldName, $useNative)
    {
        if (isset($this->entityFieldMapping[$fieldName]['property'])) {
            return $this->entityFieldMapping[$fieldName]['property'];
        }

        /** @var ClassMetadata $metaData */
        $metaData = $this->entityManager->getClassMetadata($entity);

        // Native uses the column-name and not field-name
        if ($useNative) {
            return $metaData->getColumnName($property);
        }

        return $property;
    }

    private function getMappingType($fieldName, $entity, $propertyName)
    {
        if (isset($this->entityFieldMapping[$fieldName]['mapping_type'])) {
            $type = $this->entityFieldMapping[$fieldName]['mapping_type'];
        } else {
            $type = $this->entityManager->getClassMetadata($entity)->getTypeOfField($propertyName);
        }

        if (null === $type) {
            throw new \RuntimeException(
                sprintf(
                    'Unable to determine mapping type of column "%s"#%s.'.PHP_EOL.
                    'Use setField() to configure the explicit mapping type of the field.',
                    $entity,
                    $propertyName
                )
            );
        }

        return is_object($type) ? $type : MappingType::getType($type);
    }

    private function getEntityAlias($fieldName, $entityName)
    {
        if (isset($this->entityFieldMapping[$fieldName])) {
            return $this->entityFieldMapping[$fieldName]['alias'];
        }

        if (!isset($this->entityClassMapping[$entityName])) {
            throw new \RuntimeException(
                sprintf(
                    'No entity alias mapping found for "%s". You must either set the alias mapping for entity "%1$s", '.
                    'or set the entity alias for field "%2$s".',
                    $entityName,
                    $fieldName
                )
            );
        }

        // Set the alias on the field to save some time on the next round
        return $this->entityClassMapping[$entityName];
    }
}
