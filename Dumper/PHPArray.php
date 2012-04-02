<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\RecordFilterBundle\Dumper;

use Rollerworks\RecordFilterBundle\Formatter\FormatterInterface;

/**
 * Dump the filtering preferences as 'flat' PHP Array.
 *
 * The values are flattened, meaning that the values are in one array and not per type.
 * To get the array with types its better to serialize the result of getFilters().
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class PHPArray extends AbstractDumper
{
    /**
     * Returns the filtering preference as an PHP Array.
     *
     * Each entry value is a group with the fields and there values (as Array)
     *
     * @param \Rollerworks\RecordFilterBundle\Formatter\FormatterInterface $formatter
     * @return array
     */
    public function dumpFilters(FormatterInterface $formatter)
    {
        $aFilters = array();

        foreach ($formatter->getFilters() as $groupIndex => $fields) {
            foreach ($fields as $field => $values) {
                $aFilters[$groupIndex][$field] = self::filterStructToArray($values);
            }
        }

        return $aFilters;
    }
}
