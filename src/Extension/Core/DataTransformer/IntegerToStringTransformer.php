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

namespace Rollerworks\Component\Search\Extension\Core\DataTransformer;

/**
 * Transforms between an integer and a localized number with grouping
 * (each thousand) and comma separators.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
final class IntegerToStringTransformer extends NumberToStringTransformer
{
    /**
     * Constructs a transformer.
     *
     * @param int $roundingMode One of the ROUND_ constants in this class
     */
    public function __construct(int $roundingMode = null)
    {
        parent::__construct(0, $roundingMode ?? self::ROUND_DOWN);
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        $result = parent::reverseTransform($value);

        return null !== $result ? (int) $result : null;
    }
}
