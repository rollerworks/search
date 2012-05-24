<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\RecordFilterBundle\Factory;

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
     *
     * @return string
     */
    protected function generateQuery(array $annotations)
    {
        $query = '
    protected function buildWhere()
    {
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

            if (isset($filters[$origField])) {
                $field = $filters[$origField];

                if ($field->hasSingleValues()) {
                    $query .= $Field." IN(".$this->createInList($field->getSingleValues(), $origField).") AND ";
                }

                if ($field->hasExcludes()) {
                    $query .= $Field." NOT IN(".$this->createInList($field->getExcludes(), $origField).") AND ";
                }

                if ($field->hasRanges()) {
                    foreach ($field->getRanges() as $range) {
                        $query .= "$Field BETWEEN ".$this->getValStr($range->getLower(), $origField)." AND ".$this->getValStr($range->getUpper(), $origField)." AND ";
                    }
                }

                if ($field->hasExcludedRanges()) {
                    foreach ($field->getExcludedRanges() as $range) {
                        $query .= "$Field NOT BETWEEN ".$this->getValStr($range->getLower(), $origField)." AND ".$this->getValStr($range->getUpper(), $origField)." AND ";
                    }
                }

                if ($field->hasCompares()) {
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
