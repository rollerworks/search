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

    /**
     * Constructor.
     *
     * @param Validator|null $validator
     */
    public function __construct(?Validator $validator = null)
    {
        $this->validator = $validator ?? new NullValidator();
    }

    /**
     * Checks if the maximum group nesting level is exceeded.
     *
     * @param string $path
     */
    protected function validateGroupNesting(string $path)
    {
        if ($this->level > $this->config->getMaxNestingLevel()) {
            throw new GroupsNestingException(
                $this->config->getMaxNestingLevel(), $path
            );
        }
    }

    /**
     * Checks if the maximum group count is exceeded.
     *
     * @param int    $count
     * @param string $path
     */
    protected function validateGroupsCount(int $count, string $path)
    {
        if ($count > $this->config->getMaxGroups()) {
            throw new GroupsOverflowException($this->config->getMaxGroups(), $path);
        }
    }

    /**
     * Ensure the nesting level returned to 0.
     *
     * This method is called after processing and helps with
     * finding bugs.
     */
    protected function assertLevel0()
    {
        if ($this->level > 0) {
            throw new \RuntimeException('Level nesting is not reset to 0, please report this bug.');
        }
    }
}
