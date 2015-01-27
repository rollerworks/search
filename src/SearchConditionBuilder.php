<?php

/*
 * This file is part of the RollerworksSearch Component package.
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
     */
    public function __construct($logical = ValuesGroup::GROUP_LOGICAL_AND, FieldSet $fieldSet = null, SearchConditionBuilder $parent = null)
    {
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
    public static function create(FieldSet $fieldSet = null, $logical = ValuesGroup::GROUP_LOGICAL_AND)
    {
        return new self($logical, $fieldSet);
    }

    /**
     * @param $logical
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
     * @return SearchCondition
     *
     * @throws BadMethodCallException when there is no FieldSet configured.
     */
    public function getSearchCondition()
    {
        if ($this->parent) {
            return $this->parent->getSearchCondition();
        }

        if (null === $this->fieldSet) {
            throw new BadMethodCallException('Unable to create SearchCondition without FieldSet.');
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
