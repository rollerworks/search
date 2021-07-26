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
use Throwable;

/**
 * Allows to use multiple transformers based on their type.
 *
 * This transformer is mostly used by DateTimeType to support
 * both DateTime and DateInterval objects.
 *
 * @internal
 */
final class MultiTypeDataTransformer implements DataTransformer
{
    /** @var array<class-string,DataTransformer> */
    private $transformers;

    /**
     * @param array<class-string,DataTransformer> $transformers
     */
    public function __construct(array $transformers)
    {
        $this->transformers = $transformers;
    }

    public function transform($value)
    {
        if ($value === null) {
            return '';
        }

        $type = get_debug_type($value);

        if (! isset($this->transformers[$type])) {
            throw new TransformationFailedException(\sprintf('Unsupported type "%s".', $type));
        }

        return $this->transformers[$type]->transform($value);
    }

    public function reverseTransform($value)
    {
        $finalException = null;

        foreach ($this->transformers as $transformer) {
            try {
                return $transformer->reverseTransform($value);
            } catch (Throwable $e) {
                $finalException = new TransformationFailedException($e->getMessage() . \PHP_EOL . $e->getTraceAsString(), $e->getCode(), $finalException);

                continue;
            }
        }

        throw $finalException;
    }
}
