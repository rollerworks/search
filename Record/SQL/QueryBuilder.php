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

use \Doctrine\ORM\EntityManager;

use Rollerworks\RecordFilterBundle\Formatter\FormatterInterface;
use Rollerworks\RecordFilterBundle\Value\FilterValuesBag;

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