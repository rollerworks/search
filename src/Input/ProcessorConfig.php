<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Input;

use Rollerworks\Component\Search\FieldSet;

class ProcessorConfig
{
    /**
     * @var int
     */
    private $maxNestingLevel = 100;

    /**
     * @var int
     */
    private $maxValues = 10000;

    /**
     * @var int
     */
    private $maxGroups = 100;

    /**
     * @var FieldSet
     */
    private $fieldSet;

    /**
     * @param FieldSet $fieldSet
     */
    public function __construct(FieldSet $fieldSet)
    {
        $this->fieldSet = $fieldSet;
    }

    /**
     * Returns the FieldSet.
     *
     * @return FieldSet
     */
    public function getFieldSet()
    {
        return $this->fieldSet;
    }

    /**
     * Set the maximum group nesting level.
     *
     * @param int $maxNestingLevel
     */
    public function setMaxNestingLevel($maxNestingLevel)
    {
        $this->maxNestingLevel = $maxNestingLevel;
    }

    /**
     * Gets the maximum group nesting level.
     *
     * @return int
     */
    public function getMaxNestingLevel()
    {
        return $this->maxNestingLevel;
    }

    /**
     * Set the maximum number of values per group.
     *
     * @param int $maxValues
     */
    public function setMaxValues($maxValues)
    {
        $this->maxValues = $maxValues;
    }

    /**
     * Get the maximum number of values per group.
     *
     * @return int
     */
    public function getMaxValues()
    {
        return $this->maxValues;
    }

    /**
     * Set the maximum number of groups per nesting level.
     *
     * To calculate an absolute maximum use following formula:
     * maxGroups * maxNestingLevel.
     *
     * @param int $maxGroups
     */
    public function setMaxGroups($maxGroups)
    {
        $this->maxGroups = $maxGroups;
    }

    /**
     * Get the maximum number of groups per nesting level.
     *
     * @return int
     */
    public function getMaxGroups()
    {
        return $this->maxGroups;
    }
}
