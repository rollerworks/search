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

use Rollerworks\Component\Search\Input\ProcessorConfig;

/**
 * InputProcessorInterface must be implemented by each input-processor.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface InputProcessorInterface
{
    /**
     * Process the input and returns the result.
     *
     * The processor should only handle fields
     * that are registered in the FieldSet.
     *
     * @param ProcessorConfig $config Configuration for the processor
     * @param mixed           $input  Input to process, depends on the
     *                                processor implementation
     *
     * @throws Exception\InvalidSearchConditionException When search condition is created
     *                                                   but has errors
     * @throws Exception\ValuesOverflowException         When maximum values count is exceeded
     * @throws Exception\FieldRequiredException          When a field is required but missing
     * @throws Exception\UnknownFieldException           When an unknown field is given in the input
     *
     * @return null|SearchConditionInterface Returns null on empty input
     */
    public function process(ProcessorConfig $config, $input);
}
