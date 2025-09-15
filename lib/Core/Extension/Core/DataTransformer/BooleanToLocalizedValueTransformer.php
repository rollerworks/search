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

use Rollerworks\Component\Search\DataTransformer;
use Rollerworks\Component\Search\Exception\TransformationFailedException;

final class BooleanToLocalizedValueTransformer implements DataTransformer
{
    public function __construct(
        private string $trueLabel = 'yes',
        private string $falseLabel = 'no',
        private array $trueValues = ['true', '1', 1, 'on', 'yes'],
        private array $falseValues = ['false', '0', 0, 'off', 'no'],
    ) {
    }

    public function transform(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (! \is_bool($value)) {
            throw new TransformationFailedException(\sprintf('Expected a boolean value, got "%s".', \gettype($value)));
        }

        return $value ? $this->trueLabel : $this->falseLabel;
    }

    public function reverseTransform(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! \is_scalar($value)) {
            throw new TransformationFailedException(\sprintf('Expected a scalar value, got "%s".', \gettype($value)));
        }

        if (\is_string($value)) {
            $value = mb_strtolower($value);
        }

        if (\in_array($value, $this->trueValues, true)) {
            return true;
        }

        if (\in_array($value, $this->falseValues, true)) {
            return false;
        }

        $valueTransformer = static fn (mixed $value): string => \is_string($value) ? \sprintf('"%s"', $value) : (string) $value;

        $trueValues = implode(', ', array_map($valueTransformer, $this->trueValues));
        $falseValues = implode(', ', array_map($valueTransformer, $this->falseValues));

        throw new TransformationFailedException(\sprintf('Expected one of (%s) or (%s), got %s.', $trueValues, $falseValues, $valueTransformer($value)));
    }
}
