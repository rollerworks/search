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

use Rollerworks\Component\Search\DataTransformerInterface;
use Rollerworks\Component\Search\Exception\UnexpectedTypeException;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 * @author Florian Eckerstorfer <florian@eckerstorfer.org>
 */
abstract class BaseDateTimeTransformer implements DataTransformerInterface
{
    /**
     * @var string
     */
    protected $inputTimezone;

    /**
     * @var string
     */
    protected $outputTimezone;

    /**
     * Constructor.
     *
     * @param string $inputTimezone  The name of the input timezone
     * @param string $outputTimezone The name of the output timezone
     *
     * @throws UnexpectedTypeException if a timezone is not a string
     */
    public function __construct(string $inputTimezone = null, string $outputTimezone = null)
    {
        $this->inputTimezone = $inputTimezone ?? date_default_timezone_get();
        $this->outputTimezone = $outputTimezone ?? date_default_timezone_get();
    }
}
