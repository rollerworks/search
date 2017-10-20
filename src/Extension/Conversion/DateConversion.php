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

namespace Rollerworks\Component\Search\Elasticsearch\Extension\Conversion;

use Rollerworks\Component\Search\Elasticsearch\ValueConversion;
use Rollerworks\Component\Search\Extension\Core\DataTransformer\DateTimeToStringTransformer;

/**
 * Class DateConversion.
 */
class DateConversion implements ValueConversion
{
    /**
     * @var DateTimeToStringTransformer
     */
    private $transformer;

    public function __construct()
    {
        $this->transformer = new DateTimeToStringTransformer(null, 'UTC', 'Y-m-d');
    }

    /**
     * Returns the converted value as a valid Elasticsearch value.
     *
     * @param \DateTime $value
     *
     * @throws \Rollerworks\Component\Search\Exception\TransformationFailedException
     *
     * @return string
     */
    public function convertValue($value): string
    {
        return $this->transformer->transform($value);
    }
}
