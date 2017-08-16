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
use Rollerworks\Component\Search\Exception\InvalidArgumentException;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 * @author Florian Eckerstorfer <florian@eckerstorfer.org>
 */
abstract class BaseDateTimeTransformer implements DataTransformer
{
    /**
     * @var string
     */
    protected $inputTimezone;

    /**
     * @var string
     */
    protected $outputTimezone;

    protected static $formats = [
        \IntlDateFormatter::NONE,
        \IntlDateFormatter::FULL,
        \IntlDateFormatter::LONG,
        \IntlDateFormatter::MEDIUM,
        \IntlDateFormatter::SHORT,
    ];

    /**
     * Constructor.
     *
     * @param string|null $inputTimezone  The name of the input timezone
     * @param string|null $outputTimezone The name of the output timezone
     */
    public function __construct(?string $inputTimezone = null, ?string $outputTimezone = null)
    {
        $this->inputTimezone = $inputTimezone ?? date_default_timezone_get();
        $this->outputTimezone = $outputTimezone ?? date_default_timezone_get();

        // Check if input and output timezones are valid
        try {
            new \DateTimeZone($this->inputTimezone);
        } catch (\Exception $e) {
            throw new InvalidArgumentException(sprintf('Input timezone is invalid: %s.', $this->inputTimezone), $e->getCode(), $e);
        }

        try {
            new \DateTimeZone($this->outputTimezone);
        } catch (\Exception $e) {
            throw new InvalidArgumentException(sprintf('Output timezone is invalid: %s.', $this->outputTimezone), $e->getCode(), $e);
        }
    }
}
