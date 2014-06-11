<?php

/**
 * This file is part of RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
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
     * Serialize a SearchCondition instance.
     *
     * This removes the FieldSet object, and replaces it with
     * a FieldSet-name to ensure the proper FieldSet when unserializing.
     *
     * @param SearchConditionInterface $searchCondition
     *
     * @return array [fieldSet-name, ValuesGroup]
     */
    public static function serialize(SearchConditionInterface $searchCondition)
    {
        return array($searchCondition->getFieldSet()->getSetName(), serialize($searchCondition->getValuesGroup()));
    }

    /**
     * Unserialize the SearchCondition.
     *
     * @param FieldSet $fieldSet        FieldSet object to assign to SearchCondition, must match original fieldset-name
     * @param array    $searchCondition [fieldSet-name, ValuesGroup]
     *
     * @return SearchCondition
     *
     * @throws InvalidArgumentException when passed SearchCondition is invalid (values-mismatch, wrong fieldset, failed unserializing)
     */
    public static function unserialize(FieldSet $fieldSet, array $searchCondition)
    {
        if (count($searchCondition) <> 2 || !isset($searchCondition[0], $searchCondition[1]) ) {
            throw new InvalidArgumentException('Serialized SearchCondition must be exactly two values [fieldSet-name, ValuesGroup].');
        }

        if ($fieldSet->getSetName() !== $searchCondition[0]) {
            throw new InvalidArgumentException(sprintf('Wrong FieldSet, expected FieldSet "%s", but got "%s".', $searchCondition[0], $fieldSet->getSetName()));
        }

        if (!$group = @unserialize($searchCondition[1])) {
            throw new InvalidArgumentException('Unable to unserialize invalid value.');
        }

        return new SearchCondition($fieldSet, $group);
    }

    /**
     * This class should not be initialized.
     */
    private function __construct()
    {
    }
}
