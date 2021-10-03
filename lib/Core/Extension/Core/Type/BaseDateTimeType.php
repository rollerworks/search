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

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
abstract class BaseDateTimeType extends AbstractFieldType
{
    public const DEFAULT_DATE_FORMAT = \IntlDateFormatter::MEDIUM;
    public const DEFAULT_TIME_FORMAT = \IntlDateFormatter::MEDIUM;

    protected static $acceptedFormats = [
        \IntlDateFormatter::FULL,
        \IntlDateFormatter::LONG,
        \IntlDateFormatter::MEDIUM,
        \IntlDateFormatter::SHORT,
    ];

    protected function validateFormat(string $name, $value): void
    {
        if (! \in_array($value, self::$acceptedFormats, true)) {
            throw new InvalidConfigurationException(
                'The "' . $name . '" option must be one of the IntlDateFormatter constants ' .
                '(FULL, LONG, MEDIUM, SHORT) or the "pattern" option must be a string representing a custom format.'
            );
        }
    }

    protected function validateDateFormat(string $name, string $format): void
    {
        if ($format !== null
            && (mb_strpos($format, 'y') === false || mb_strpos($format, 'M') === false || mb_strpos($format, 'd') === false)
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
