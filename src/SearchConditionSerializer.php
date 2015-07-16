<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search;

use Rollerworks\Component\Search\Exception\InvalidArgumentException;

/**
 * SearchConditionSerializer, serializes a search condition for persistent storage.
 *
 * In practice this serializes the root ValuesGroup and of the condition
 * and bundles it with the FieldSet-name for future unserializing.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class SearchConditionSerializer
{
    /**
     * @var FieldSetRegistry
     */
    private $fieldSetRegistry;

    /**
     * Constructor.
     *
     * @param FieldSetRegistryInterface $fieldSetRegistry A FieldSet registry for loading
     *                                                    FieldSet configurations
     */
    public function __construct(FieldSetRegistryInterface $fieldSetRegistry)
    {
        $this->fieldSetRegistry = $fieldSetRegistry;
    }

    /**
     * Serialize a search condition.
     *
     * The returned value is an array, you should serialize it yourself.
     * This is not done already because storing a serialized SearchCondition
     * in a php session would serialize the serialized result again.
     *
     * @param SearchConditionInterface $searchCondition
     *
     * @throws InvalidArgumentException when the FieldSet of the search condition
     *                                  is not registered in the FieldSetRegistry
     *
     * @return array [FieldSet-name, serialized ValuesGroup object]
     */
    public function serialize(SearchConditionInterface $searchCondition)
    {
        $setName = $searchCondition->getFieldSet()->getSetName();

        if (!$this->fieldSetRegistry->has($setName)) {
            throw new InvalidArgumentException(
                sprintf(
                    'FieldSet "%s" is not registered in the FieldSetRegistry, '.
                    'you should register the FieldSet before serializing the search condition.',
                    $setName
                )
            );
        }

        return [$setName, serialize($searchCondition->getValuesGroup())];
    }

    /**
     * Unserialize a serialized search condition.
     *
     * @param array $searchCondition [FieldSet-name, serialized ValuesGroup object]
     *
     * @throws InvalidArgumentException when serialized SearchCondition is invalid
     *                                  (invalid structure or failed to unserialize)
     *
     * @return SearchCondition
     */
    public function unserialize($searchCondition)
    {
        if (2 !== count($searchCondition) || !isset($searchCondition[0], $searchCondition[1])) {
            throw new InvalidArgumentException(
                'Serialized search condition must be exactly two values [FieldSet-name, serialized ValuesGroup].'
            );
        }

        $fieldSet = $this->fieldSetRegistry->get($searchCondition[0]);

        if (false === $group = unserialize($searchCondition[1])) {
            throw new InvalidArgumentException('Unable to unserialize invalid value.');
        }

        return new SearchCondition($fieldSet, $group);
    }
}
