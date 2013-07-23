<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Exporter;

use Rollerworks\Bundle\RecordFilterBundle\Type\FilterTypeInterface;

/**
 * AbstractExporter.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
abstract class AbstractExporter implements ExporterInterface
{
    /**
     * @param FilterTypeInterface $type
     * @param string              $value
     *
     * @return string
     */
    protected static function dumpValue(FilterTypeInterface $type = null, $value)
    {
        if ($type) {
            $value = $type->dumpValue($value);
        }

        return (string) $value;
    }

    /**
     * @param FilterTypeInterface $type
     * @param string              $value
     *
     * @return string
     */
    protected static function formatValue(FilterTypeInterface $type = null, $value)
    {
        if ($type) {
            $value = $type->formatOutput($value);
        }

        return (string) $value;
    }
}
