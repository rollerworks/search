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

use Rollerworks\Component\Search\Exception\BadMethodCallException;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class SearchConditionBuilder
{
    /**
     * @var ValuesGroup
     */
    protected $valuesGroup;

    /**
     * @var SearchConditionBuilder
     */
    protected $parent;

    /**
     * @var FieldSet
     */
    protected $fieldSet;

    /**
     * Constructor.
     *
     * @param string                 $logical
     * @param FieldSet               $fieldSet
     * @param SearchConditionBuilder $parent
     *
     * @internal Usage of this method is protected as of v1.0.0-beta5 and access will be changed to protected
     *           in 2.0. Use the static create() method instead.
     *
     * @throws BadMethodCallException when no FieldSet is provided
     */
    public function __construct($logical = ValuesGroup::GROUP_LOGICAL_AND, FieldSet $fieldSet = null, SearchConditionBuilder $parent = null)
    {
        if (null === $fieldSet && null === $parent) {
            throw new BadMethodCallException('Unable to create SearchCondition without FieldSet.');
        }

        $this->valuesGroup = new ValuesGroup($logical);
        $this->parent = $parent;
        $this->fieldSet = $fieldSet;
    }

    /**
     * Creates a new SearchConditionBuilder.
     *
     * @param FieldSet $fieldSet
     * @param string   $logical
     *
     * @return SearchConditionBuilder
     */
    public static function create(FieldSet $fieldSet, $logical = ValuesGroup::GROUP_LOGICAL_AND)
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
    public function group($logical = ValuesGroup::GROUP_LOGICAL_AND)
    {
        $builder = new self($logical, null, $this);
        $this->valuesGroup->addGroup($builder->getGroup());

        return $builder;
    }

    /**
     * Add/expend a field on this ValuesGroup and returns the object instance.
     *
     * The object instance is a ValuesBagBuilder (subset of ValuesBag), which
     * allows to add extra values to the field:
     *
     * ->field('name')
     *   ->addSingleValue(new SingleValue('my value'))
     *   ->addSingleValue(new SingleValue('my value 2'))
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
    public function field($name, $forceNew = false)
    {
        if (!$forceNew && $this->valuesGroup->hasField($name)) {
            $valuesBag = $this->valuesGroup->getField($name);
        } else {
            $valuesBag = new ValuesBagBuilder($this);
            $this->valuesGroup->addField($name, $valuesBag);
        }

        return $valuesBag;
    }

    /**
     * @return SearchConditionBuilder
     */
    public function end()
    {
        return null !== $this->parent ? $this->parent : $this;
    }

    /**
     * @return ValuesGroup
     */
    public function getGroup()
    {
        return $this->valuesGroup;
    }

    /**
     * Build the SearchCondition object using the groups and fields.
     *
     * @return SearchCondition
     */
    public function getSearchCondition()
    {
        if ($this->parent) {
            return $this->parent->getSearchCondition();
        }

        return new SearchCondition($this->fieldSet, $this->valuesGroup);
    }

    /**
     * @return FieldSet
     */
    public function getFieldSet()
    {
        return $this->fieldSet;
    }
}
