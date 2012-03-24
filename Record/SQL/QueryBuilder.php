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

namespace Rollerworks\RecordFilterBundle\Record\SQL;

use \Doctrine\ORM\EntityManager;

use Rollerworks\RecordFilterBundle\Formatter\FormatterInterface;
use Rollerworks\RecordFilterBundle\FilterStruct;

/**
 * RecordFilter SQL QueryBuilder class.
 *
 * Creates the WHERE clause using Doctrine.
 * The WHERE case is returned as string, CASTING is (currently) not supported.
 *
 * The parent (this class's) __construct() should always be called.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
abstract class QueryBuilder extends AbstractSQL
{
    /**
     * Doctrine EntityManager
     *
     * @var \Doctrine\ORM\EntityManager
     */
    protected $entityManager = null;

    /**
     * Formatter instance
     *
     * @var \Rollerworks\RecordFilterBundle\Formatter\FormatterInterface
     */
    protected $formatter;

    /**
     * Constructor
     *
     * @param FormatterInterface  $formatter
     * @param EntityManager       $entityManager
     */
    public function __construct(FormatterInterface $formatter, EntityManager $entityManager)
    {
        $this->formatter     = $formatter;
        $this->entityManager = $entityManager;
    }

    /**
     * Get the correct fieldname.
     *
     * Handling aliases.
     *
     * @param string $fieldname
     * @return string
     */
    protected function getFieldRef($fieldname)
    {
        if (isset($this->aliases[$fieldname])) {
            return $this->aliases[$fieldname];
        }
        else {
            return $fieldname;
        }
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
    protected function getValStr($value, $fieldname)
    {
        if (is_object($value) || is_array($value)) {
            throw new \InvalidArgumentException('Only basic types are accepted.');
        }

        if ((is_integer($value) || ctype_digit($value)) || is_float($value)) {
            return $value;
        }
        elseif (preg_match('/^[+-]?(([0-9]+)|([0-9]*\.[0-9]+|[0-9]+\.[0-9]*)|(([0-9]+|([0-9]*\.[0-9]+|[0-9]+\.[0-9]*))[eE][+-]?[0-9]+))$/', $value)) {
            return floatval($value);
        }
        else {
            return $this->entityManager->getConnection()->quote($value);
        }
    }
}