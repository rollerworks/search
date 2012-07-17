<?php

/**
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Mapping;

use Metadata\PropertyMetadata as BasePropertyMetadata;
use Rollerworks\Bundle\RecordFilterBundle\Record\Sql\SqlValueConversionInterface;

/**
 * PropertyMetadata.
 */
class PropertyMetadata extends BasePropertyMetadata
{
    public $filter_name;
    public $required;

    public $acceptRanges;
    public $acceptCompares;

    /**
     * @var FilterTypeConfig
     */
    public $type;

    public $widgetsConfig = array();
    public $sqlConversion = array('class' => null, 'params' => array());

    /**
     * Set SQL conversion configuration.
     *
     * @param string $class
     * @param array  $params
     *
     * @throws \InvalidArgumentException
     */
    public function setSqlConversion($class, array $params = array())
    {
        if (!class_exists($class)) {
            throw new \InvalidArgumentException(sprintf('Failed to find SqlConversion class "%s".', $class));
        }

        $r = new \ReflectionClass($class);

        if ($r->isAbstract()) {
            throw new \InvalidArgumentException(sprintf('SqlConversion class "%s" can\'t be abstract.', $class));
        }

        if (!$r->implementsInterface('\\Rollerworks\\Bundle\\RecordFilterBundle\\Record\\Sql\\SqlValueConversionInterface')) {
            throw new \InvalidArgumentException(sprintf('SqlConversion class "%s" must implement Rollerworks\\Bundle\\RecordFilterBundle\\Record\\Sql\\SqlValueConversionInterface.', $class));
        }

        if ($r->hasMethod('__construct') && !$r->getMethod('__construct')->isPublic()) {
            throw new \InvalidArgumentException(sprintf('%s::__construct(): must be public.', $class));
        }

        $this->sqlConversion = array('class' => $class, 'params' => $params);
    }

    /**
     * @return boolean
     */
    public function hasSqlConversion()
    {
        return null !== $this->sqlConversion['class'];
    }

    /**
     * @return string|null
     */
    public function getSqlConversionClass()
    {
        return $this->sqlConversion['class'];
    }

    /**
     * @return array|null
     */
    public function getSqlConversionParams()
    {
        return $this->sqlConversion['params'];
    }

    /**
     * @return string
     */
    public function serialize()
    {
        return serialize(array(
            $this->class,
            $this->name,
            $this->filter_name,
            $this->type,

            $this->required,
            $this->acceptRanges,
            $this->acceptCompares,

            $this->sqlConversion,
            $this->widgetsConfig
        ));
    }

    /**
     * @param string $str
     *
     * @return mixed
     */
    public function unserialize($str)
    {
        list(
            $this->class,
            $this->name,
            $this->filter_name,
            $this->type,

            $this->required,
            $this->acceptRanges,
            $this->acceptCompares,

            $this->sqlConversion,
            $this->widgetsConfig
        ) = unserialize($str);

        $this->reflection = new \ReflectionProperty($this->class, $this->name);
        $this->reflection->setAccessible(true);
    }
}
