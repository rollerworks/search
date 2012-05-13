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
use Rollerworks\RecordFilterBundle\Value\FilterValuesBag;

/**
 * Dump the filtering preferences as JSON (JavaScript Object Notation).
 *
 * @link http://www.json.org/
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class JSON extends AbstractDumper
{
    /**
     * Returns the filtering preference as JSON.
     *
     * The returned structure depends on $flattenValues.
     *
     * When the values are flattened they are in the following format.
     * Each entry value is a group with the fields and there values (as Array)
     *
     * Example:
     * <code>
     *  [ { "field1" : [ "value1", "value2" ] }, { "field1" : [ "value1", "value2" ] } ]
     * </code>
     *
     * In none-flattened format, the fields are returned as follow.
     * Each entry value is a group with the fields and there values per type, types maybe empty.
     *
     * <code>
     *  [{
     *      "field1": {
     *          "single-values": ["value", "value2"],
     *          "excluded-values": ["value", "value2"],
     *          "ranges": [{
     *              "lower": "12",
     *              "higher": "20"
     *          }],
     *          "excluded-ranges": [{
     *              "lower": "12",
     *              "higher": "20"
     *          }],
     *          "compares": [{
     *              "opr": ">",
     *              "value": "value"
     *          }]
     *      }
     *  }, {
     *      "field1": {
     *          "single-values": ["value", "value2"]
     *      }
     *  }]
     * </code>
     *
     * @param FormatterInterface $formatter
     * @param boolean            $flattenValues
     * @return string JSON array
     */
    public function dumpFilters(FormatterInterface $formatter, $flattenValues = false)
    {
        $filters = array();

        if ($flattenValues) {
            foreach ($formatter->getFilters() as $groupIndex => $fields) {
                foreach ($fields as $field => $values) {
                    $filters[$groupIndex][$field] = self::filterStructToArray($formatter, $field, $values);
                }
            }
        }
        else {
            foreach ($formatter->getFilters() as $groupIndex => $fields) {
                foreach ($fields as $field => $values) {
                    $filters[$groupIndex][$field] = self::createField($values);
                }
            }
        }

        return json_encode($filters);
    }

    /**
     * Create the field {Object}
     *
     * @param FilterValuesBag $filter
     * @return array
     */
    static private function createField(FilterValuesBag $filter)
    {
        $field = array();

        if ($filter->hasSingleValues()) {
            $field['single-values'] = array();

            foreach ($filter->getSingleValues() as $value) {
                $field['single-values'][] = (string) $value->getValue();
            }
        }

        if ($filter->hasExcludes()) {
            $field['excluded-values'] = array();

            foreach ($filter->getExcludes() as $value) {
                $field['excluded-values'][] = (string) $value->getValue();
            }
        }

        if ($filter->hasRanges()) {
            $field['ranges'] = array();

            foreach ($filter->getRanges() as $range) {
                $field['ranges'] = array('lower'  => (string) $range->getLower(),
                                         'higher' => (string) $range->getUpper());
            }
        }

        if ($filter->hasExcludedRanges()) {
            $field['excluded-ranges'] = array();

            foreach ($filter->getExcludedRanges() as $range) {
                $field['excluded-ranges'] = array('lower'  => (string) $range->getLower(),
                                                  'higher' => (string) $range->getUpper());
            }
        }

        if ($filter->hasCompares()) {
            $field['compares'] = array();

            foreach ($filter->getCompares() as $compare) {
                $field['compares'][] = array('opr'   => (string) $compare->getOperator(),
                                             'value' => (string) $compare->getValue());
            }
        }

        return $field;
    }
}
