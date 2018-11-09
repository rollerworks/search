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

use Rollerworks\Component\Search\ConditionErrorMessage;
use Rollerworks\Component\Search\ErrorList;
use Rollerworks\Component\Search\Exception\GroupsNestingException;
use Rollerworks\Component\Search\Exception\GroupsOverflowException;
use Rollerworks\Component\Search\InputProcessor;

/**
 * AbstractInput provides the shared logic for the InputProcessors.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
abstract class AbstractInput implements InputProcessor
{
    /**
     * @var ProcessorConfig
     */
    protected $config;

    /**
     * Error messages.
     *
     * Must be an ErrorList to allow passing by reference
     * in the ConditionStructure(ByView)Builder.
     *
     * @var ConditionErrorMessage[]|ErrorList
     */
    protected $errors;

    /**
     * Current nesting level.
     *
     * @var int
     */
    protected $level = 0;

    /**
     * @var Validator
     */
    protected $validator;

    public function __construct(?Validator $validator = null)
    {
        $this->validator = $validator ?? new NullValidator();
    }

    protected function validateGroupNesting(string $path): void
    {
        if ($this->level > $this->config->getMaxNestingLevel()) {
            throw new GroupsNestingException(
                $this->config->getMaxNestingLevel(), $path
            );
        }
    }

    protected function validateGroupsCount(int $count, string $path)
    {
        if ($count > $this->config->getMaxGroups()) {
            throw new GroupsOverflowException($this->config->getMaxGroups(), $path);
        }
    }

    /**
     * This method is called after processing and helps with finding bugs.
     */
    protected function assertLevel0()
    {
        if ($this->level > 0) {
            throw new \RuntimeException('Level nesting is not reset to 0, please report this bug.');
        }
    }
}
