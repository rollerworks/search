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

use Rollerworks\Component\Search\Exception\GroupsNestingException;
use Rollerworks\Component\Search\Exception\GroupsOverflowException;
use Rollerworks\Component\Search\Exception\UnsupportedValueTypeException;
use Rollerworks\Component\Search\FieldConfigInterface;
use Rollerworks\Component\Search\InputProcessorInterface;

/**
 * AbstractInput provides the shared logic for the InputProcessors.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
abstract class AbstractInput implements InputProcessorInterface
{
    /**
     * @var ProcessorConfig
     */
    protected $config;

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
        if ($nestingLevel > $this->config->getMaxNestingLevel()) {
            throw new GroupsNestingException(
                $this->config->getMaxNestingLevel(),
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
        if ($count > $this->config->getMaxGroups()) {
            throw new GroupsOverflowException($this->config->getMaxGroups(), $count, $groupIdx, $nestingLevel);
        }
    }

    /**
     * Checks if the given field accepts the given value-type.
     *
     * @param FieldConfigInterface $fieldConfig
     * @param string               $type
     *
     * @throws UnsupportedValueTypeException
     *
     * @deprecated
     */
    protected function assertAcceptsType(FieldConfigInterface $fieldConfig, $type)
    {
        if (!$fieldConfig->supportValueType($type)) {
            throw new UnsupportedValueTypeException($fieldConfig->getName(), $type);
        }
    }
}
