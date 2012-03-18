<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Rollerscapes
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link    http://projects.rollerscapes.net/RollerFramework
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */

namespace Rollerworks\RecordFilterBundle\Dumper;

use Rollerworks\RecordFilterBundle\Formatter\FormatterInterface;

/**
 * Dump the filtering preferences as RecordFilter FilterQuery string.
 *
 * @see \Rollerworks\RecordFilterBundle\Input
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class FilterQuery extends AbstractDumper
{
    /**
     * Returns the filtering preference as an 'raw' FilterQuery string.
     *
     * Single values and ranges are always quoted.
     *
     * @param \Rollerworks\RecordFilterBundle\Formatter\FormatterInterface $formatter
     * @param bool                                                                   $fieldPerLine  Return each field on a new line
     * @return string
     */
    public function dumpFilters(FormatterInterface $formatter, $fieldPerLine = false)
    {
        $filterQuery = '';

        foreach ($formatter->getFilters() as $fields) {
            $filterQuery .= '( ';

            foreach ($fields as $label => $values) {
                $filterQuery .= $label . '=' . implode(', ', self::filterStructToArray($values, true)) . '; ';

                if ($fieldPerLine) {
                    $filterQuery = rtrim($filterQuery);
                    $filterQuery .= PHP_EOL;
                }
            }

            $filterQuery = rtrim($filterQuery) . ' ), ';
        }

        $filterQuery = rtrim($filterQuery, ', ');

        return $filterQuery;
    }
}
