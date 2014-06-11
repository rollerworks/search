<?php

/**
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Extension\Core\DataTransformer;

use Rollerworks\Component\Search\DataTransformerInterface;

/**
 * Transforms between a date string and a DateTime object
 * and between a localized string and a integer.
 */
class BirthdayTransformer implements DataTransformerInterface
{
    /**
     * @var DataTransformerInterface[]
     */
    private $transformers;

    /**
     * @param DataTransformerInterface[] $transformers
     */
    public function __construct($transformers)
    {
        $this->transformers = $transformers;
    }

    /**
     * {@inheritDoc}
     */
    public function transform($value)
    {
        if (ctype_digit($value)) {
            return $this->getNumberFormatter()->format($value, \NumberFormatter::DECIMAL);
        }

        foreach ($this->transformers as $transformer) {
            $value = $transformer->transform($value);
        }

        return $value;
    }

    /**
     * {@inheritDoc}
     */
    public function reverseTransform($value)
    {
        if (ctype_digit($value)) {
            return $value;
        } elseif (preg_match('/^\p{N}+$/', $value)) {
            return $this->getNumberFormatter()->parse($value, \NumberFormatter::DECIMAL);
        }

        $transformers = $this->transformers;

        for ($i = count($transformers) - 1; $i >= 0; --$i) {
            $value = $transformers[$i]->reverseTransform($value);
        }

        return $value;
    }

    /**
     * Returns a preconfigured \NumberFormatter instance
     *
     * @return \NumberFormatter
     */
    protected function getNumberFormatter()
    {
        /** @var \NumberFormatter $formatter */
        static $formatter;

        if (!$formatter || $formatter->getLocale() !== \Locale::getDefault()) {
            $formatter = new \NumberFormatter(\Locale::getDefault(), \NumberFormatter::DECIMAL);
            $formatter->setAttribute(\NumberFormatter::GROUPING_USED, false);
        }

        return $formatter;
    }
}
