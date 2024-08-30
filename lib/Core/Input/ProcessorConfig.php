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

use Rollerworks\Component\Search\Exception\InputProcessorException;
use Rollerworks\Component\Search\FieldSet;

/**
 * Holds the configuration for an Input processor.
 */
class ProcessorConfig
{
    /**
     * @var int
     */
    private $maxNestingLevel = 5;

    /**
     * @var int
     */
    private $maxValues = 100;

    /**
     * @var int
     */
    private $maxGroups = 10;

    /**
     * @var FieldSet
     */
    private $fieldSet;

    private int|\DateInterval|null $cacheTTL = null;

    /** @var string|null */
    private $defaultField;

    public function __construct(FieldSet $fieldSet)
    {
        $this->fieldSet = $fieldSet;
    }

    public function getFieldSet(): FieldSet
    {
        return $this->fieldSet;
    }

    /**
     * Set the maximum group nesting level.
     *
     * @return $this
     */
    public function setMaxNestingLevel(int $maxNestingLevel): static
    {
        $this->maxNestingLevel = $maxNestingLevel;

        return $this;
    }

    /**
     * Gets the maximum group nesting level.
     */
    public function getMaxNestingLevel(): int
    {
        return $this->maxNestingLevel;
    }

    /**
     * Set the maximum number of values per group.
     *
     * @return $this
     */
    public function setMaxValues(int $maxValues): static
    {
        $this->maxValues = $maxValues;

        return $this;
    }

    /**
     * Get the maximum number of values per group.
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
     * @return $this
     */
    public function setMaxGroups(int $maxGroups): static
    {
        $this->maxGroups = $maxGroups;

        return $this;
    }

    /**
     * Get the maximum number of groups per nesting level.
     */
    public function getMaxGroups(): int
    {
        return $this->maxGroups;
    }

    /**
     * @return $this
     */
    public function setCacheTTL(\DateInterval|int|null $cacheTTL): self
    {
        $this->cacheTTL = $cacheTTL;

        return $this;
    }

    public function getCacheTTL(): \DateInterval|int|null
    {
        return $this->cacheTTL;
    }

    public function getDefaultField(bool $error = false): ?string
    {
        if ($this->defaultField === null && $error) {
            throw new InputProcessorException('', 'No default field configured. Please provide a field name.');
        }

        return $this->defaultField;
    }

    /**
     * @return $this
     */
    public function setDefaultField(string $defaultField): static
    {
        $this->defaultField = $defaultField;

        return $this;
    }
}
