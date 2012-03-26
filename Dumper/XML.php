<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Rollerscapes
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @link    http://projects.rollerscapes.net/RollerFramework
 * @license http://www.opensource.org/licenses/lgpl-license.php LGPL
 */

namespace Rollerworks\RecordFilterBundle\Dumper;

use Rollerworks\RecordFilterBundle\Formatter\FormatterInterface;

use Rollerworks\RecordFilterBundle\FilterStruct;
use Rollerworks\RecordFilterBundle\Struct\Range;

/**
 * Dump the filtering preferences as formatted XML (Extensible Markup Language).
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class XML implements DumperInterface
{
    /**
     * Returns the filtering preference as formatted XML.
     *
     * @param \Rollerworks\RecordFilterBundle\Formatter\FormatterInterface $formatter
     * @return string
     */
    public function dumpFilters(FormatterInterface $formatter)
    {
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        $filters = $dom->createElement('filters');
        $groups  = $dom->createElement('groups');

        foreach ($formatter->getFilters() as $fields) {
            $group = $dom->createElement('group');

            foreach ($fields as $field => $values) {
                $fieldNode = $dom->createElement('field');
                $fieldNode->setAttribute('name', $field);

                self::createField($values, $fieldNode, $dom);

                if (count($fieldNode->childNodes) > 0) {
                    $group->appendChild($fieldNode);
                }
            }

            if (count($group->childNodes) > 0) {
                $groups->appendChild($group);
            }
        }

        if (count($groups->childNodes) < 1) {
            throw new \UnexpectedValueException('No groups or filters where returned by the formatter.');
        }

        $filters->appendChild($groups);
        $dom->appendChild($filters);

        return $dom->saveXML();
    }

    /**
     * Populates the field-node
     *
     * @param \Rollerworks\RecordFilterBundle\FilterStruct  $filter
     * @param \DOMNode                                      $fieldNode
     * @param \DOMDocument                                  $dom
     */
    private static function createField(FilterStruct $filter, \DOMNode $fieldNode, \DOMDocument $dom)
    {
        if ($filter->hasSingleValues()) {
            $singleValues = $dom->createElement('single-values');

            foreach ($filter->getSingleValues() as $value) {
                $valueNode = $dom->createElement('value');

                $valueNode->appendChild($dom->createTextNode((string) $value));
                $singleValues->appendChild($valueNode);
            }

            $fieldNode->appendChild($singleValues);
        }

        if ($filter->hasExcludes()) {
            $excludedValues = $dom->createElement('excluded-values');

            foreach ($filter->getExcludes() as $value) {
                $valueNode = $dom->createElement('value');

                $valueNode->appendChild($dom->createTextNode((string) $value));
                $excludedValues->appendChild($valueNode);
            }

            $fieldNode->appendChild($excludedValues);
        }

        if ($filter->hasRanges()) {
            $ranges = $dom->createElement('ranges');

            foreach ($filter->getRanges() as $range) {
                $ranges->appendChild(self::createRangeNode($range, $dom));
            }

            $fieldNode->appendChild($ranges);
        }

        if ($filter->hasExcludedRanges()) {
            $ranges = $dom->createElement('excluded-ranges');

            foreach ($filter->getExcludedRanges() as $range) {
                $ranges->appendChild(self::createRangeNode($range, $dom));
            }

            $fieldNode->appendChild($ranges);
        }

        if ($filter->hasCompares()) {
            $compares = $dom->createElement('compares');

            foreach ($filter->getCompares() as $compare) {
                $compareNode = $dom->createElement('compare');
                $compareNode->setAttribute('opr', $compare->getOperator());
                $compareNode->appendChild($dom->createTextNode((string) $compare->getValue()));

                $compares->appendChild($compareNode);
            }

            $fieldNode->appendChild($compares);
        }
    }

    /**
     * Creates an range node and returns it
     *
     * @param \Rollerworks\RecordFilterBundle\Struct\Range $range
     * @param \DOMDocument                                           $dom
     * @return \DOMElement
     */
    private static function createRangeNode(Range $range, \DOMDocument $dom)
    {
        $rangeNode = $dom->createElement('range');

        $lowerValNode = $dom->createElement('lower');
        $lowerValNode->appendChild($dom->createTextNode((string) $range->getLower()));

        $higherValNode = $dom->createElement('higher');
        $higherValNode->appendChild($dom->createTextNode((string) $range->getUpper()));

        $rangeNode->appendChild($lowerValNode);
        $rangeNode->appendChild($higherValNode);

        return $rangeNode;
    }

    /**
     * Set to return the output to an read friendly format.
     *
     * Setting this will make the XML more readable but also increase the content size.
     */
    public function setFormatOutput()
    {
    }
}
