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

namespace Rollerworks\RecordFilterBundle\Factory;

use Rollerworks\RecordFilterBundle\Formatter\Formatter;

/**
 * This abstract SQL factory class provides the shared logic to create the RecordFilter::Record::SQL::* Classes at runtime.
 * The information is read from the Annotations of the Class.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
abstract class AbstractSQLFactory extends AbstractFactory
{
    /**
     * Get Query WHERE-statement from the Class Annotations
     *
     * @param array $annotations
     * @return string
     */
    protected function generateQuery(array $annotations)
    {
        $query = '
    protected function buildWhere(){
        $FG = $this->formatter->getFilters();
        $query = "";

        foreach ($FG as $groupIndex => $filters) {
            $query .= "(";'."\n";

        foreach ($annotations as $annotation) {
            if (!$annotation instanceof \Rollerworks\RecordFilterBundle\Annotation\Field) {
                continue;
            }

            /** @var \Rollerworks\RecordFilterBundle\Annotation\Field $annotation */

            $query .= '
            $origField = '.var_export($annotation->getName(), true).'; $Field = $this->getFieldRef($origField);

            if(isset($filters[$origField])) {
                $field = $filters[$origField];

                if($field->hasSingleValues()) {
                    $query .= $Field." IN(".$this->createInList($field->getSingleValues(), $origField).") AND ";
                }

                if($field->hasExcludes()) {
                    $query .= $Field." NOT IN(".$this->createInList($field->getExcludes(), $origField).") AND ";
                }

                if($field->hasRanges()) {
                    foreach ($field->getRanges() as $range) {
                        $query .= "$Field BETWEEN ".$this->getValStr($range->getLower(), $origField)." AND ".$this->getValStr($range->getUpper(), $origField)." AND ";
                    }
                }

                if($field->hasExcludedRanges()) {
                    foreach ($field->getExcludedRanges() as $range) {
                        $query .= "$Field NOT BETWEEN ".$this->getValStr($range->getLower(), $origField)." AND ".$this->getValStr($range->getUpper(), $origField)." AND ";
                    }
                }

                if($field->hasCompares()) {
                    foreach ($field->getCompares() as $comp) {
                        $query .= "$Field ".$comp->getOperator()." ".$this->getValStr($comp->getValue(), $origField)." AND ";
                    }
                }
            }';
        }

        $query .= '         $query = trim($query, " AND ");'."\n";
        $query .= '         $query .= ")\n OR ";';
        $query .= "\n       }\n\n";

        $query .= '     $query = trim($query, " OR ");'."\n";
        $query .= '     return $query;'."\n";
        $query .= ' }'. "\n";

        return trim($query);
    }
}
