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

namespace Rollerworks\Component\Search\ApiPlatform\Doctrine\Orm\Extension;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Core\Exception\RuntimeException;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface;
use Doctrine\ORM\QueryBuilder;
use Rollerworks\Component\Search\ApiPlatform\ArrayKeysValidator;
use Rollerworks\Component\Search\Doctrine\Orm\ConditionGenerator;
use Rollerworks\Component\Search\Doctrine\Orm\DoctrineOrmFactory;
use Rollerworks\Component\Search\SearchCondition;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Applies the RollerworksSearch SearchCondition on a resource query.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class SearchExtension implements QueryCollectionExtensionInterface
{
    private $requestStack;
    private $resourceMetadataFactory;
    private $ormFactory;

    public function __construct(RequestStack $requestStack, ResourceMetadataFactoryInterface $resourceMetadataFactory, DoctrineOrmFactory $ormFactory)
    {
        $this->resourceMetadataFactory = $resourceMetadataFactory;
        $this->requestStack = $requestStack;
        $this->ormFactory = $ormFactory;
    }

    public function applyToCollection(QueryBuilder $queryBuilder, QueryNameGeneratorInterface $queryNameGenerator, string $resourceClass, string $operationName = null)
    {
        $request = $this->requestStack->getCurrentRequest();

        /** @var SearchCondition $condition */
        if (!$request || null === $condition = $request->attributes->get('_api_search_condition')) {
            return;
        }

        if (!method_exists($queryBuilder, 'setHint')) {
            return;
        }

        $fieldSetName = $condition->getFieldSet()->getSetName() ?? '*';
        $attributes = $this->resourceMetadataFactory->create($resourceClass)->getAttribute('rollerworks_search');

        if (empty($attributes['doctrine_orm']['*']) && empty($attributes['doctrine_orm'][$fieldSetName])) {
            return;
        }

        $setName = isset($attributes['doctrine_orm'][$fieldSetName]) ? $fieldSetName : '*';
        $configuration = $attributes['doctrine_orm'][$setName];

        ArrayKeysValidator::assertOnlyKeys($configuration, ['accepted_fieldsets', 'relations', 'mappings'], $resourceClass.'#attributes:rollerworks_search.doctrine_orm['.$setName.']');

        $this->configureRelations($resourceClass, $configuration, $setName, $queryBuilder);

        $conditionGenerator = $this->ormFactory->createCachedConditionGenerator(
            $this->ormFactory->createConditionGenerator($queryBuilder, $condition)
        );

        $this->configureMappings($resourceClass, $configuration, $setName, $conditionGenerator);
        $conditionGenerator->updateQuery();
    }

    private function configureRelations(string $resourceClass, array $configuration, string $setName, QueryBuilder $queryBuilder): void
    {
        if (empty($configuration['relations'])) {
            return;
        }

        $path = $resourceClass.'#attributes:rollerworks_search.doctrine_orm['.$setName.'][relations]';

        if (isset($configuration['relations']['o'])) {
            throw new RuntimeException(sprintf('Invalid configuration for "%s", relation name "o" is already used for the root.', $path));
        }

        foreach ($configuration['relations'] as $alias => $config) {
            $path .= "[$alias]";

            ArrayKeysValidator::assertOnlyKeys($config, ['join', 'entity', 'type', 'conditionType', 'condition', 'index'], $path);
            ArrayKeysValidator::assertKeysExists($config, ['join', 'entity'], $path);

            $config['type'] = $config['type'] ?? 'left';
            if (!method_exists($queryBuilder, $config['type'].'Join')) {
                throw new RuntimeException(sprintf('Invalid value for "%s", type "%s" is not supported. Use left, right or inner.', $path.'[type]', $config['type']));
            }

            $queryBuilder->{$config['type'].'Join'}(
                $config['join'],
                $alias,
                $config['conditionType'] ?? null,
                $config['condition'] ?? null,
                $config['index'] ?? null
            );
        }
    }

    private function configureMappings(string $resourceClass, array $configuration, string $setName, ConditionGenerator $conditionGenerator): void
    {
        $configuration['relations']['o']['entity'] = $resourceClass;

        foreach ($configuration['mappings'] as $mappingName => $mapping) {
            if (!is_array($mapping)) {
                $mapping = [
                    'alias' => 'o',
                    'property' => $mapping,
                ];
            }

            if (empty($mapping['alias'])) {
                $mapping['alias'] = 'o';
            }

            $path = $resourceClass.'#attributes:rollerworks_search.doctrine_orm['.$setName.'][mappings]['.$mappingName.']';
            ArrayKeysValidator::assertOnlyKeys($mapping, ['property', 'alias', 'type'], $path);
            ArrayKeysValidator::assertKeysExists($mapping, ['property'], $path);

            if (!isset($configuration['relations'][$mapping['alias']])) {
                throw new RuntimeException(sprintf('Invalid value for "%s", alias "%s" is not registered in the "relations".', $path.'[alias]', $mapping['alias']));
            }

            $conditionGenerator->setField(
                $mappingName,
                $mapping['property'],
                $mapping['alias'],
                $configuration['relations'][$mapping['alias']]['entity'],
                $mapping['type'] ?? null
            );
        }
    }
}
