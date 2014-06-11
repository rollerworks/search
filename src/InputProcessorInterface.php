<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
