<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search;

use Rollerworks\Component\Search\Exception\TransformationFailedException;

/**
 * Transforms a value between different representations.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface DataTransformerInterface
{
    /**
     * Transforms a value from the original representation to a transformed representation.
     *
     * This method is called on two occasions:
     *
     * 1. When data from an input is submitted using to transform the new input data
     * back into the renderable format. For example if you have a date field and submit '2009-10-10'
     * you might accept this value because its easily parsed, but the transformer still writes back
     * "2009/10/10" onto the display field (for further displaying or other purposes).
     *
     * This method must be able to deal with empty values. Usually this will
     * be NULL, but depending on your implementation other empty values are
     * possible as well (such as empty strings). The reasoning behind this is
     * that value transformers must be chainable. If the transform() method
     * of the first value transformer outputs NULL, the second value transformer
     * must be able to process that value.
     *
     * By convention, transform() should return an empty string if NULL is
     * passed.
     *
     * @param mixed $value The value in the original representation
     *
     * @throws TransformationFailedException When the transformation fails.
     *
     * @return mixed The value in the transformed representation
     */
    public function transform($value);

    /**
     * Transforms a value from the transformed representation to its original
     * representation.
     *
     * This method is called to transform the requests tainted data
     * into an acceptable format for your data processing layer.
     *
     * This method must be able to deal with empty values. Usually this will
     * be an empty string, but depending on your implementation other empty
     * values are possible as well (such as empty strings). The reasoning behind
     * this is that value transformers must be chainable. If the
     * reverseTransform() method of the first value transformer outputs an
     * empty string, the second value transformer must be able to process that
     * value.
     *
     * By convention, reverseTransform() should return NULL if an empty string
     * is passed.
     *
     * @param mixed $value The value in the transformed representation
     *
     * @throws TransformationFailedException When the transformation fails.
     *
     * @return mixed The value in the original representation
     */
    public function reverseTransform($value);
}
