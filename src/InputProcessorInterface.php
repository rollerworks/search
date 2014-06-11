<?php

/**
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search;

/**
 * InputProcessorInterface.
 *
 * Processes the provided input and returns the ValuesGroup
 * for further formatting.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface InputProcessorInterface
{
    /**
     * Returns the FieldSet.
     *
     * @return FieldSet
     */
    public function getFieldSet();

    /**
     * Process the input and returns the result.
     *
     * The processor should only handle fields
     * that are registered in the FieldSet.
     *
     * Unknown fields must throw an NoFieldException.
     *
     * @param mixed $input
     *
     * @return null|SearchConditionInterface Returns null on empty input
     */
    public function process($input);
}
