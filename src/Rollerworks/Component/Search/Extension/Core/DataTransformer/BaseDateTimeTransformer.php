<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
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
     * @var array
     */
    protected static $formats = array(
        \IntlDateFormatter::NONE,
        \IntlDateFormatter::FULL,
        \IntlDateFormatter::LONG,
        \IntlDateFormatter::MEDIUM,
        \IntlDateFormatter::SHORT,
    );

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
    public function __construct($inputTimezone = null, $outputTimezone = null)
    {
        if (!is_string($inputTimezone) && null !== $inputTimezone) {
            throw new UnexpectedTypeException($inputTimezone, 'string');
        }

        if (!is_string($outputTimezone) && null !== $outputTimezone) {
            throw new UnexpectedTypeException($outputTimezone, 'string');
        }

        $this->inputTimezone = $inputTimezone ?: date_default_timezone_get();
        $this->outputTimezone = $outputTimezone ?: date_default_timezone_get();
    }
}
