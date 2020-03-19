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

namespace Rollerworks\Component\Search;

use Rollerworks\Component\Search\Input\ProcessorConfig;

/**
 * The InputProcessor must be implemented by all input-processor.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface InputProcessor
{
    /**
     * Process the input and returns the result.
     *
     * The processor should only handle fields that are registered in
     * the FieldSet.
     *
     * @param ProcessorConfig $config Configuration for the processor
     * @param mixed           $input  Input to process, actual format depends
     *                                on the processor implementation
     *
     * @throws Exception\InvalidSearchConditionException When there are errors in the input
     *                                                   this can be a failed transformation
     *                                                   or processing error
     */
    public function process(ProcessorConfig $config, $input): SearchCondition;
}
