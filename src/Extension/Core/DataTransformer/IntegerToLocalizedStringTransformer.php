<?php

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
class IntegerToLocalizedStringTransformer extends NumberToLocalizedStringTransformer
{
    /**
     * Constructs a transformer.
     *
     * @param int  $precision    Unused
     * @param bool $grouping     Whether thousands should be grouped
     * @param int  $roundingMode One of the ROUND_ constants in this class
     */
    public function __construct($precision = null, $grouping = null, $roundingMode = self::ROUND_DOWN)
    {
        if (null === $roundingMode) {
            $roundingMode = self::ROUND_DOWN;
        }

        parent::__construct(0, $grouping, $roundingMode);
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
