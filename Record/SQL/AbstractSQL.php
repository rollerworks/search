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

namespace Rollerworks\RecordFilterBundle\Record\SQL;

use Rollerworks\RecordFilterBundle\Formatter\FormatterInterface;

/**
 * AbstractSQL
 *
 * The abstract SQL factory class provides the shared logic for creating an SQL WHERE-clause based on the RecordFilter.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
abstract class AbstractSQL implements SQLRecordInterface
{
    /**
     * Field-aliases.
     * Stored as: [engine-name][alias-name] => SQL-field-name
     *
     * @var Array
     */
    protected $aliases = array();

    /**
     * Final WHERE-case
     *
     * @var string
     */
    protected $whereCase = null;

    /**
     * @var \Rollerworks\RecordFilterBundle\Formatter\FormatterInterface
     */
    protected $formatter;

    /**
     * @var \Doctrine\DBAL\Connection
     */
    protected $DBAL = null;

    /**
     * Set the SQL field reference of the field.
     *
     * @param string $fieldname      Field-name as given by the formatter
     * @param string $dbField        SQL Field reference
     * @param string $engine         Use 'this' alias for only for this database-engine (name is as \Doctrine\DBAL\Driver#getName())
     * @return \Rollerworks\RecordFilterBundle\Record\SQL\WhereStruct
     */
    public function setFieldAlias($fieldname, $dbField, $engine = 'all')
    {
        $this->aliases[$engine][$fieldname] = $dbField;

        return $this;
    }

    /**
     * Get the SQL WHERE Clause
     *
     * @return string
     */
    public function getWhereClause()
    {
        if (null === $this->whereCase) {
            $this->whereCase = $this->buildWhere();
        }

        return $this->whereCase;
    }

    /**
     * Get the correct field-name.
     *
     * Handling the aliases.
     *
     * @param string $fieldname
     * @return string
     */
    protected function getFieldRef($fieldname)
    {
        if (isset($this->DBAL)) {
            $sEngine = $this->DBAL->getDriver()->getName();
        }
        else {
            $sEngine = 'all';
        }

        if (isset($this->aliases[$sEngine][$fieldname])) {
            return $this->aliases[$sEngine][$fieldname];
        }
        elseif (isset($this->aliases['all'][$fieldname])) {
            return $this->aliases['all'][$fieldname];
        }
        else {
            return $fieldname;
        }
    }

    /**
     * Returns an comma-separated list of values.
     *
     * @param \Rollerworks\RecordFilterBundle\Struct\Value[] $values
     * @param string                                                   $fieldname
     * @return string
     */
    protected function createInList($values, $fieldname)
    {
        $inList = '';

        foreach ($values as $oValue) {
            $inList .= $this->getValStr($oValue->getValue(), $fieldname) . ', ';
        }

        return trim($inList, ', ');
    }

    /**
     * Get an single value string.
     *
     * The value is quoted when needed.
     *
     * @param string $value
     * @param string $fieldname
     * @return float|integer|string
     */
    protected abstract function getValStr($value, $fieldname);

    /**
     * Build the WHERE-clause
     *
     * And returns the output result
     *
     * @return string
     */
    abstract protected function buildWhere();
}