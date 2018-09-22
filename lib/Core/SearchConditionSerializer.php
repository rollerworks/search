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
    private $searchFactory;

    public function __construct(SearchFactory $searchFactory)
    {
        $this->searchFactory = $searchFactory;
    }

    /**
     * Serialize a search condition.
     *
     * The returned value is an array, you should serialize it yourself.
     * This is not done already because storing a serialized SearchCondition
     * in a php session would serialize the serialized result again.
     *
     * Caution: The FieldSet must be loadable from the factory.
     *
     * @param SearchCondition $searchCondition
     *
     * @return array [FieldSet-name, serialized ValuesGroup object]
     */
    public function serialize(SearchCondition $searchCondition): array
    {
        $setName = $searchCondition->getFieldSet()->getSetName();

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
    public function unserialize(array $searchCondition): SearchCondition
    {
        if (2 !== \count($searchCondition) || !isset($searchCondition[0], $searchCondition[1])) {
            throw new InvalidArgumentException(
                'Serialized search condition must be exactly two values [FieldSet-name, serialized ValuesGroup].'
            );
        }

        $fieldSet = $this->searchFactory->createFieldSet($searchCondition[0]);

        if (false === $group = unserialize($searchCondition[1])) {
            throw new InvalidArgumentException('Unable to unserialize invalid value.');
        }

        return new SearchCondition($fieldSet, $group);
    }
}
