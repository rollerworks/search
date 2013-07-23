<?php

/*
 * This file is part of the RollerworksRecordFilterBundle package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Exporter;

use Rollerworks\Bundle\RecordFilterBundle\Formatter\FormatterInterface;

/**
 * Exports the filtering preferences as a JSON string.
 *
 * @see \Rollerworks\Bundle\RecordFilterBundle\Input\JsonIput
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class JsonExporter extends ArrayExporter
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function dumpFilters(FormatterInterface $formatter)
    {
        return json_encode(parent::dumpFilters($formatter));
    }

}
