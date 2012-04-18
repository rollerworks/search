<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\RecordFilterBundle\Record\SQL;

use Rollerworks\RecordFilterBundle\Formatter\FormatterInterface;
use Rollerworks\RecordFilterBundle\Value\FilterValuesBag;

/**
 * RecordFilter SQL Where-case structure class.
 *
 * This class provides the basic functionality needed for creating an SQL WHERE-clause based on the RecordFilter.
 * An solid class must extend this class and provide its own __construct() and buildWhere()
 *
 * The parent (this class) __construct() should always be called.
 *
 * The WHERE clause is generated as ANSI SQL and may not work in all DB-engines.
 * Binary values are not support due missing proper support in the DBAL.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
abstract class WhereStruct extends AbstractSQL
{
    /**
     * Field casting configuration.
     * Stored as: [engine-name][field-name] => CAST-type
     *
     * @var Array
     */
    protected $fieldCasts = array();

    /**
     * Constructor
     *
     * @param FormatterInterface  $formatter
     * @param Connection          $DBAL
     */
    public function __construct(FormatterInterface $formatter, \Doctrine\DBAL\Connection $DBAL)
    {
        $this->formatter = $formatter;
        $this->DBAL      = $DBAL;
    }

    /**
     * Set the type casting for an filter-value.
     *
     * Casting is not needed for integer and float.
     * BYTE/BYTEA are not directly supported. Pre-convert them before usage.
     *
     * @param string $fieldname    Fieldname as-it-is registered, no alias
     * @param string $castType     SQL-type to cast to
     * @param string $engine       Use the casting only for this database-engine (name is-as \Doctrine\DBAL\Driver#getName())
     * @return WhereStruct
     *
     * @api
     */
    public function setFieldCast($fieldname, $castType, $engine = 'all')
    {
        $this->fieldCasts[$engine][$fieldname] = $castType;

        return $this;
    }

    /**
     * Get an single value string.
     *
     * The value is quoted when needed.
     *
     * @param string $value
     * @param string $fieldname
     * @return float|integer|string
     *
     * @api
     */
    protected function getValStr($value, $fieldname)
    {
        if (is_object($value) || is_array($value)) {
            throw new \InvalidArgumentException('Only basic types are accepted.');
        }

        if ((is_integer($value) || ctype_digit($value)) || is_float($value)) {
            return $value;
        }
        elseif (preg_match('#^[-]?(([0-9]*[\.][0-9]+)|([0-9]+[\.][0-9]*))$#s', $value)) {
            return floatval($value);
        }
        else {
            if (isset($this->fieldCasts[$this->DBAL->getDriver()->getName()][$fieldname])) {
                $cast = $this->fieldCasts[$this->DBAL->getDriver()->getName()][$fieldname];
            }
            elseif (isset($this->fieldCasts['all'][$fieldname])) {
                $cast = $this->fieldCasts['all'][$fieldname];
            }

            $value = $this->DBAL->quote($value);

            if (!empty($cast)) {
                return "CAST($value AS $cast)";
            }

            return $value;
        }
    }
}