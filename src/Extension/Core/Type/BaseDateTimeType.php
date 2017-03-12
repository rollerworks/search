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

namespace Rollerworks\Component\Search\Extension\Core\Type;

use Rollerworks\Component\Search\Exception\InvalidConfigurationException;
use Rollerworks\Component\Search\Field\AbstractFieldType;
use Rollerworks\Component\Search\ValueComparator;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
abstract class BaseDateTimeType extends AbstractFieldType
{
    const DEFAULT_DATE_FORMAT = \IntlDateFormatter::MEDIUM;
    const DEFAULT_TIME_FORMAT = \IntlDateFormatter::MEDIUM;

    /**
     * @var ValueComparator
     */
    protected $valueComparator;

    /**
     * @var array
     */
    protected static $acceptedFormats = [
        \IntlDateFormatter::FULL,
        \IntlDateFormatter::LONG,
        \IntlDateFormatter::MEDIUM,
        \IntlDateFormatter::SHORT,
    ];

    /**
     * Constructor.
     *
     * @param ValueComparator $valueComparator
     */
    public function __construct(ValueComparator $valueComparator)
    {
        $this->valueComparator = $valueComparator;
    }

    protected function validateFormat(string $name, $value)
    {
        if (!in_array($value, self::$acceptedFormats, true)) {
            throw new InvalidConfigurationException(
                'The "'.$name.'" option must be one of the IntlDateFormatter constants '.
                '(FULL, LONG, MEDIUM, SHORT) or the "pattern" option must be a string representing a custom format.'
            );
        }
    }

    protected function validateDateFormat(string $name, string $format)
    {
        if (null !== $format &&
            (false === strpos($format, 'y') || false === strpos($format, 'M') || false === strpos($format, 'd'))
        ) {
            throw new InvalidConfigurationException(
                sprintf(
                    'The "%s" option should contain the letters "y", "M" and "d". Its current value is "%s".',
                    $name,
                    $format
                )
            );
        }
    }
}
