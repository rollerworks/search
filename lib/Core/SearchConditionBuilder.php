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
     * @var SearchConditionBuilder|null
     */
    private $parent;

    /**
     * @var FieldSet
     */
    private $fieldSet;

    public static function create(FieldSet $fieldSet, string $logical = ValuesGroup::GROUP_LOGICAL_AND): self
    {
        return new self($logical, $fieldSet);
    }

    /**
     * Create a new ValuesGroup and returns the object instance.
     *
     * Afterwards the group can be expended with fields or subgroups:
     *
     * ```
     * ->group()
     *     ->field('name')
     *         ->...
     *     ->end() // return back to the ValuesGroup.
     * ->end() // return back to the parent ValuesGroup
     * ```
     *
     * @param string $logical eg. one of the following ValuesGroup class constants value:
     *                        GROUP_LOGICAL_OR or GROUP_LOGICAL_AND
     */
    public function group(string $logical = ValuesGroup::GROUP_LOGICAL_AND): self
    {
        $builder = new self($logical, $this->fieldSet, $this);
        $this->valuesGroup->addGroup($builder->getGroup());

        return $builder;
    }

    /**
     * Add/expend a field ValuesBag on this ValuesGroup and returns
     * the ValuesBag.
     *
     * Note. Values must be in the model format, they are not transformed!
     *
     * The ValuesBagBuilder is subset of ValuesBag, which provides a developer
     * friendly interface to construct a ValuesBag structure for the field.
     *
     * ```
     * ->field('name')
     *   ->addSimpleValue('my value')
     *   ->addSimpleValue('my value 2')
     * ->end() // return back to the ValuesGroup
     * ```
     *
     * Tip! If the field already exists the existing ValuesBagBuilder of the field
     * is returned). To force a new builder use `->field('name', true)` instead,
     * this will overwrite the current field's ValuesBag.
     */
    public function field(string $name, bool $forceNew = false): ValuesBagBuilder // XXX This should be split into two methods: field and overwriteField
    {
        if (!$forceNew && $this->valuesGroup->hasField($name)) {
            /** @var ValuesBagBuilder $valuesBag */
            $valuesBag = $this->valuesGroup->getField($name);
        } else {
            $valuesBag = new ValuesBagBuilder($this);
            $this->valuesGroup->addField($name, $valuesBag);
        }

        return $valuesBag;
    }

    public function end(): self
    {
        return $this->parent ?? $this;
    }

    public function getGroup(): ValuesGroup
    {
        return $this->valuesGroup;
    }

    /**
     * Build the SearchCondition object using the groups and fields.
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

    private function __construct(string $logical, FieldSet $fieldSet, self $parent = null)
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
