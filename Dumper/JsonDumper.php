<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Dumper;

use Rollerworks\Bundle\RecordFilterBundle\Formatter\FormatterInterface;

/**
 * Dumps the filtering preferences as a JSON string.
 *
 * @see \Rollerworks\Bundle\RecordFilterBundle\Input\JsonIput
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class JsonDumper extends ArrayDumper
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
