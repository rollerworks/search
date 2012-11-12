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

use Rollerworks\Bundle\RecordFilterBundle\Formatter\FormatterInterface;

/**
 * ExporterInterface for exporting the filtering preference.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
interface ExporterInterface
{
    /**
     * Returns the filters in a exported format.
     *
     * @param FormatterInterface $formatter
     *
     * @api
     */
    public function dumpFilters(FormatterInterface $formatter);
}
