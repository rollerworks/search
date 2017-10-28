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

use Rollerworks\Component\Search\Value\ValuesGroup;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class SearchConditionBuilder
{
    /**
     * @var ValuesGroup
     */
    private $valuesGroup;

    /**
     * @var SearchConditionBuilder
     */
    private $parent;

    /**
     * @var FieldSet
     */
    private $fieldSet;

    /**
     * Creates a new SearchConditionBuilder.
     *
     * @param FieldSet $fieldSet
     * @param string   $logical
     *
     * @return SearchConditionBuilder
     */
    public static function create(FieldSet $fieldSet, string $logical = ValuesGroup::GROUP_LOGICAL_AND): SearchConditionBuilder
    {
        return new self($logical, $fieldSet);
    }

    /**
     * Create a new ValuesGroup and returns the object instance.
     *
     * After creating the group it can be expended with fields or subgroups:
     *
     * ->group()
     *     ->field('name')
     *         ->...
     *     ->end() // return back to the ValuesGroup.
     * ->end() // return back to the parent ValuesGroup
     *
     * @param string $logical eg. one of the following ValuesGroup class constants value:
     *                        GROUP_LOGICAL_OR or GROUP_LOGICAL_AND
     *
     * @return SearchConditionBuilder
     */
    public function group(string $logical = ValuesGroup::GROUP_LOGICAL_AND): SearchConditionBuilder
    {
        $builder = new self($logical, $this->fieldSet, $this);
        $this->valuesGroup->addGroup($builder->getGroup());

        return $builder;
    }

    /**
     * Add/expend a field on this ValuesGroup and returns the object instance.
     *
     * Note. Values must be in the model format, they are not transformed!
     *
     * The object instance is a ValuesBagBuilder (subset of ValuesBag), which
     * allows to add extra values to the field:
     *
     * ->field('name')
     *   ->addSimpleValue('my value')
     *   ->addSimpleValue('my value 2')
     * ->end() // return back to the ValuesGroup
     *
     * Tip! If the field already exists the existing is expended (values are added).
     * To force an overwrite of the existing field use `->field('name', true)` instead.
     *
     * @param string $name
     * @param bool   $forceNew
     *
     * @return ValuesBagBuilder
     */
    public function field(string $name, bool $forceNew = false): ValuesBagBuilder
    {
        if (!$forceNew && $this->valuesGroup->hasField($name)) {
            $valuesBag = $this->valuesGroup->getField($name);
        } else {
            $valuesBag = new ValuesBagBuilder($this);
            $this->valuesGroup->addField($name, $valuesBag);
        }

        return $valuesBag;
    }

    public function end(): SearchConditionBuilder
    {
        return $this->parent ?? $this;
    }

    /**
     * @return ValuesGroup
     */
    public function getGroup(): ValuesGroup
    {
        return $this->valuesGroup;
    }

    /**
     * Build the SearchCondition object using the groups and fields.
     *
     * @return SearchCondition
     */
    public function getSearchCondition(): SearchCondition
    {
        if ($this->parent) {
            return $this->parent->getSearchCondition();
        }

        // This the root of the condition so now traverse back up the hierarchy.
        // We need to re-create the condition using actual objects.
        $rootValuesGroup = new ValuesGroup($this->valuesGroup->getGroupLogical());
        $this->normalizeValueGroup($this->valuesGroup, $rootValuesGroup);

        return new SearchCondition($this->fieldSet, $rootValuesGroup);
    }

    private function __construct(string $logical, FieldSet $fieldSet, SearchConditionBuilder $parent = null)
    {
        $this->valuesGroup = new ValuesGroup($logical);
        $this->parent = $parent;
        $this->fieldSet = $fieldSet;
    }

    private function normalizeValueGroup(ValuesGroup $currentValuesGroup, ValuesGroup $rootValuesGroup): void
    {
        foreach ($currentValuesGroup->getGroups() as $group) {
            $subGroup = new ValuesGroup($group->getGroupLogical());
            $this->normalizeValueGroup($group, $subGroup);

            $rootValuesGroup->addGroup($subGroup);
        }

        foreach ($currentValuesGroup->getFields() as $name => $values) {
            if ($values instanceof ValuesBagBuilder) {
                $values = $values->toValuesBag();
            }

            $rootValuesGroup->addField($name, $values);
        }
    }
}
