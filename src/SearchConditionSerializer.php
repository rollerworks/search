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
 * SearchConditionSerializer, serializes a SearchCondition for storage.
 *
 * The returned value is an array, you should serialize it yourself.
 * This is not done because storing it in session would serialize
 * the string again.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class SearchConditionSerializer
{
    /**
     * @var FieldSetRegistry
     */
    private $fieldSetRegistry;

    public function __construct(FieldSetRegistryInterface $fieldSetRegistry)
    {
        $this->fieldSetRegistry = $fieldSetRegistry;
    }

    /**
     * Serialize a SearchCondition instance.
     *
     * This removes the FieldSet object, and replaces it with
     * a FieldSet-name to ensure the proper FieldSet when unserializing.
     *
     * @param SearchConditionInterface $searchCondition
     *
     * @throws InvalidArgumentException when the SearchCondition's FieldSet is not
     *                                  registered in the FieldSetRegistry
     *
     * @return array [fieldSet-name, ValuesGroup]
     */
    public function serialize(SearchConditionInterface $searchCondition)
    {
        $setName = $searchCondition->getFieldSet()->getSetName();

        if (!$this->fieldSetRegistry->has($setName)) {
            throw new InvalidArgumentException(
                sprintf(
                    'FieldSet "%s" is not registered in the FieldSetRegistry,'.
                    ' you should register the FieldSet before serializing the SearchCondition.',
                    $setName
                )
            );
        }

        return array($setName, serialize($searchCondition->getValuesGroup()));
    }

    /**
     * Unserialize the SearchCondition.
     *
     * @param array $searchCondition [fieldSet-name, ValuesGroup]
     *
     * @throws InvalidArgumentException when serialized SearchCondition is invalid
     *                                  (invalid structure or failed to unserialize)
     *
     * @return SearchCondition
     */
    public function unserialize($searchCondition)
    {
        if (count($searchCondition) !== 2 || !isset($searchCondition[0], $searchCondition[1])) {
            throw new InvalidArgumentException(
                'Serialized SearchCondition must be exactly two values [fieldSet-name, serialized ValuesGroup].'
            );
        }

        $fieldSet = $this->fieldSetRegistry->get($searchCondition[0]);

        if (false === $group = unserialize($searchCondition[1])) {
            throw new InvalidArgumentException('Unable to unserialize invalid value.');
        }

        return new SearchCondition($fieldSet, $group);
    }
}
