<?php

/**
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Input;

use Rollerworks\Component\Search\Exception\GroupsNestingException;
use Rollerworks\Component\Search\Exception\GroupsOverflowException;
use Rollerworks\Component\Search\Exception\UnknownFieldException;
use Rollerworks\Component\Search\Exception\UnsupportedValueTypeException;
use Rollerworks\Component\Search\FieldAliasResolverInterface;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\InputProcessorInterface;

/**
 * AbstractInput provides the shared logic for the InputProcessors.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
abstract class AbstractInput implements InputProcessorInterface
{
    /**
     * @var int
     */
    protected $maxNestingLevel = 100;

    /**
     * @var int
     */
    protected $maxValues = 10000;

    /**
     * @var int
     */
    protected $maxGroups = 100;

    /**
     * @var FieldSet
     */
    protected $fieldSet;

    /**
     * @var FieldAliasResolverInterface|null
     */
    protected $aliasResolver;

    /**
     * Set the FieldSet for processing.
     *
     * @param FieldSet $fieldSet
     */
    public function setFieldSet(FieldSet $fieldSet)
    {
        $this->fieldSet = $fieldSet;
    }

    /**
     * Returns the FieldSet.
     *
     * @return FieldSet
     *
     * @throws \LogicException When there is no FieldSet configured.
     */
    public function getFieldSet()
    {
        if (null === $this->fieldSet) {
            throw new \LogicException('Unable to return FieldSet. No FieldSet set for the input processor.');
        }

        return $this->fieldSet;
    }

    /**
     * Set field alias resolver.
     *
     * @param FieldAliasResolverInterface $aliasResolver
     */
    public function setAliasResolver(FieldAliasResolverInterface $aliasResolver)
    {
        $this->aliasResolver = $aliasResolver;
    }

    /**
     * Get field alias resolver.
     *
     * @return FieldAliasResolverInterface
     */
    public function getAliasResolver()
    {
        return $this->aliasResolver;
    }

    /**
     * Get 'real' fieldname.
     *
     * This will pass the Field through the alias resolver.
     *
     * @param string $name
     *
     * @return string
     *
     * @throws UnknownFieldException When there is no field found.
     * @throws \LogicException       When there is no FieldSet configured.
     */
    public function getFieldName($name)
    {
        if (null === $this->fieldSet) {
            throw new \LogicException('Unable to get field. No FieldSet set for the input processor.');
        }

        if (null !== $this->aliasResolver) {
            $name = $this->aliasResolver->resolveFieldName($this->fieldSet, $name);
        }

        if (!$this->fieldSet->has($name)) {
            throw new UnknownFieldException($name);
        }

        return $name;
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

    /**
     * Checks if the maximum group nesting level is exceeded.
     *
     * @param int $groupIdx
     * @param int $nestingLevel
     *
     * @throws GroupsNestingException
     */
    protected function validateGroupNesting($groupIdx, $nestingLevel)
    {
        if ($nestingLevel > $this->maxNestingLevel) {
            throw new GroupsNestingException(
                $this->maxNestingLevel,
                $groupIdx,
                $nestingLevel
            );
        }
    }

    /**
     * Checks if the maximum group count is exceeded.
     *
     * @param int $groupIdx
     * @param int $count
     * @param int $nestingLevel
     *
     * @throws GroupsOverflowException
     */
    protected function validateGroupsCount($groupIdx, $count, $nestingLevel)
    {
        if ($count > $this->maxGroups) {
            throw new GroupsOverflowException($this->maxGroups, $count, $groupIdx, $nestingLevel);
        }
    }

    /**
     * Checks if the given field accepts the given value-type.
     *
     * @param string $type
     * @param string $field
     *
     * @throws UnsupportedValueTypeException
     */
    protected function assertAcceptsType($type, $field)
    {
        $config = $this->fieldSet->get($field);

        switch ($type) {
            case 'range':
                if (!$config->acceptRanges()) {
                    throw new UnsupportedValueTypeException($field, $type);
                }
                break;

            case 'comparison':
                if (!$config->acceptCompares()) {
                    throw new UnsupportedValueTypeException($field, $type);
                }
                break;
        }
    }
}
