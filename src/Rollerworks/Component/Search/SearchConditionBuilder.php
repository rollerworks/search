<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search;

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
     * Constructor.
     *
     * @param string                 $logical
     * @param SearchConditionBuilder $parent
     */
    public function __construct($logical = ValuesGroup::GROUP_LOGICAL_AND, SearchConditionBuilder $parent = null)
    {
        $this->valuesGroup = new ValuesGroup($logical);
        $this->parent = $parent;
    }

    /**
     * @param string $logical
     *
     * @return SearchConditionBuilder
     */
    public static function create($logical = ValuesGroup::GROUP_LOGICAL_AND)
    {
        return new self($logical);
    }

    /**
     * @param $logical
     *
     * @return SearchConditionBuilder
     */
    public function group($logical = ValuesGroup::GROUP_LOGICAL_AND)
    {
        $builder = new self($logical, $this);
        $this->valuesGroup->addGroup($builder->getGroup());

        return $builder;
    }

    /**
     * @param string $name
     *
     * @return ValuesBagBuilder
     */
    public function field($name)
    {
        $valuesBag = new ValuesBagBuilder($this);
        $this->valuesGroup->addField($name, $valuesBag);

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
}
