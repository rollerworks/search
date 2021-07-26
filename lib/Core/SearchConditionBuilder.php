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

use Rollerworks\Component\Search\Exception\BadMethodCallException;
use Rollerworks\Component\Search\Exception\InvalidArgumentException;
use Rollerworks\Component\Search\Field\OrderField;
use Rollerworks\Component\Search\Value\ValuesBag;
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

    /**
     * @var ValuesGroup|null
     */
    private $order;

    /**
     * @var self|true|null
     */
    private $primaryCondition;

    public static function create(FieldSet $fieldSet, string $logical = ValuesGroup::GROUP_LOGICAL_AND): self
    {
        return new self($logical, $fieldSet);
    }

    /**
     * Add the result-ordering of the search condition
     * and returns the SearchConditionBuilder.
     *
     * Note: This method can only be used at the root-level of the condition.
     *
     * ```
     * ->order('@name', 'ASC')
     * ->order('@id', 'DESC')
     * ->field('name')
     *   ->addSimpleValue('my value')
     *   ->addSimpleValue('my value 2')
     * ->end() // return back to the ValuesGroup
     * ```
     *
     * @return $this
     */
    public function order(string $name, string $direction = 'ASC'): self
    {
        if ($this->parent && $this->primaryCondition !== true) {
            throw new BadMethodCallException('Cannot add ordering at nested levels.');
        }

        if (! OrderField::isOrder($name)) {
            throw new InvalidArgumentException(\sprintf('Field "%s" is not a valid ordering field. Expected either "@%1$s".', $name));
        }

        $direction = \strtoupper($direction);

        if ($direction !== 'ASC' && $direction !== 'DESC') {
            throw new InvalidArgumentException(\sprintf('Invalid direction provided "%s" for field "%s", must be either "ASC" or "DESC" (case insensitive).', $direction, $name));
        }

        if ($this->order === null) {
            $this->order = new ValuesGroup();
        }

        $values = new ValuesBag();
        $values->addSimpleValue($direction);
        $this->order->addField($name, $values);

        return $this;
    }

    /**
     * Clears all the field sorting-orders (if any).
     *
     * @return $this
     */
    public function clearOrder(): self
    {
        $this->order = null;

        return $this;
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
     * Add/expend a field's ValuesBag on this ValuesGroup and returns the ValuesBag.
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
     */
    public function field(string $name, bool $forceNew = false): ValuesBagBuilder
    {
        if ($forceNew) {
            @\trigger_error(
                'Using $forceNew with true is deprecated since RollerworksSearch v2.0.0-ALPHA22 and will be removed in v2.0.0-BETA1, use overwriteField() instead.',
                \E_USER_DEPRECATED
            );

            return $this->overwriteField($name);
        }

        if (OrderField::isOrder($name)) {
            throw new InvalidArgumentException(\sprintf('Unable to configure ordering of "%s" with field(), use the order() method instead.', $name));
        }

        if ($this->valuesGroup->hasField($name)) {
            /** @var ValuesBagBuilder $valuesBag */
            $valuesBag = $this->valuesGroup->getField($name);
        } else {
            $valuesBag = new ValuesBagBuilder($this);
            $this->valuesGroup->addField($name, $valuesBag);
        }

        return $valuesBag;
    }

    /**
     * Add/overwrites a field's ValuesBag on this ValuesGroup and returns the ValuesBag.
     *
     * Note. Values must be in the model format, they are not transformed!
     *
     * The ValuesBagBuilder is subset of ValuesBag, which provides a developer
     * friendly interface to construct a ValuesBag structure for the field.
     *
     * ```
     * ->overwriteField('name')
     *   ->addSimpleValue('my value')
     *   ->addSimpleValue('my value 2')
     * ->end() // return back to the ValuesGroup
     * ```
     */
    public function overwriteField(string $name): ValuesBagBuilder
    {
        $valuesBag = new ValuesBagBuilder($this);
        $this->valuesGroup->addField($name, $valuesBag);

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
     * Build a SearchPrimaryCondition structure.
     *
     * Note: This will overwrite any existing primary-condition of this builder.
     *
     * @return self A SearchConditionBuilder for the primary-condition structure
     */
    public function primaryCondition(): self
    {
        if ($this->parent) {
            throw new BadMethodCallException('Cannot add primaryCondition at nested level.');
        }

        $builder = new self(ValuesGroup::GROUP_LOGICAL_AND, $this->fieldSet, $this);
        $builder->primaryCondition = true;

        $this->primaryCondition = $builder;

        return $builder;
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

        $searchCondition = new SearchCondition($this->fieldSet, $rootValuesGroup);

        if ($this->order) {
            $searchCondition->setOrder(new SearchOrder($this->order));
        }

        $this->buildPrimaryCondition($searchCondition);

        return $searchCondition;
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

    private function buildPrimaryCondition(SearchCondition $searchCondition): void
    {
        if (! $this->primaryCondition) {
            return;
        }

        $primaryCondition = new SearchPrimaryCondition($this->primaryCondition->valuesGroup);

        if ($this->primaryCondition->order) {
            $primaryCondition->setOrder(new SearchOrder($this->primaryCondition->order));
        }

        $searchCondition->setPrimaryCondition($primaryCondition);
    }
}
