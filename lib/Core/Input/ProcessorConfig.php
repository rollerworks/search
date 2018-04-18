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

namespace Rollerworks\Component\Search\Input;

use Rollerworks\Component\Search\FieldSet;

/**
 * ProcessorConfig holds the configuration for an Input processor.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class ProcessorConfig
{
    /**
     * @var int
     */
    private $maxNestingLevel = 100;

    /**
     * @var int
     */
    private $maxValues = 1000;

    /**
     * @var int
     */
    private $maxGroups = 100;

    /**
     * @var FieldSet
     */
    private $fieldSet;

    /**
     * @var null|int|\DateInterval
     */
    private $cacheTTL;

    /**
     * @param FieldSet $fieldSet
     */
    public function __construct(FieldSet $fieldSet)
    {
        $this->fieldSet = $fieldSet;
    }

    /**
     * @return FieldSet
     */
    public function getFieldSet(): FieldSet
    {
        return $this->fieldSet;
    }

    /**
     * Set the maximum group nesting level.
     *
     * @param int $maxNestingLevel
     */
    public function setMaxNestingLevel(int $maxNestingLevel)
    {
        $this->maxNestingLevel = $maxNestingLevel;
    }

    /**
     * Gets the maximum group nesting level.
     *
     * @return int
     */
    public function getMaxNestingLevel(): int
    {
        return $this->maxNestingLevel;
    }

    /**
     * Set the maximum number of values per group.
     *
     * @param int $maxValues
     */
    public function setMaxValues(int $maxValues)
    {
        $this->maxValues = $maxValues;
    }

    /**
     * Get the maximum number of values per group.
     *
     * @return int
     */
    public function getMaxValues(): int
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
    public function setMaxGroups(int $maxGroups)
    {
        $this->maxGroups = $maxGroups;
    }

    /**
     * Get the maximum number of groups per nesting level.
     *
     * @return int
     */
    public function getMaxGroups(): int
    {
        return $this->maxGroups;
    }

    /**
     * @param null|int|\DateInterval $cacheTTL
     *
     * @return ProcessorConfig
     */
    public function setCacheTTL($cacheTTL): self
    {
        $this->cacheTTL = $cacheTTL;

        return $this;
    }

    /**
     * @return null|int|\DateInterval
     */
    public function getCacheTTL()
    {
        return $this->cacheTTL;
    }
}
