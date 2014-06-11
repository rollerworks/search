<?php

/**
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Doctrine\Dbal;

use Rollerworks\Component\Search\Exception\BadMethodCallException;
use Rollerworks\Component\Search\Exception\UnknownFieldException;
use Rollerworks\Component\Search\SearchConditionInterface;

/**
 * Handles abstracted handling of the Doctrine WhereBuilder.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
abstract class AbstractWhereBuilder
{
    /**
     * @var SearchConditionInterface
     */
    protected $searchCondition;

    /**
     * @var \Rollerworks\Component\Search\FieldSet
     */
    protected $fieldset;

    /**
     * @var ValueConversionInterface[]|SqlValueConversionInterface[]|ConversionStrategyInterface[]
     */
    protected $valueConversions = array();

    /**
     * @var SqlFieldConversionInterface[]
     */
    protected $fieldConversions = array();

    /**
     * @var string
     */
    protected $parameterPrefix;

    /**
     * @var string
     */
    protected $whereClause;

    /**
     * @var array
     */
    protected $fields = array();

    /**
     * @var QueryGenerator
     */
    protected $queryGenerator;

    /**
     * Set the converters for a field.
     *
     * Setting is done per type (field or value), any existing conversions are overwritten.
     *
     * @param string                                               $fieldName
     * @param ValueConversionInterface|SqlFieldConversionInterface $converter
     *
     * @return self
     *
     * @throws UnknownFieldException  When the field is not registered in the fieldset.
     * @throws BadMethodCallException When the where-clause is already generated.
     */
    public function setConverter($fieldName, $converter)
    {
        if ($this->whereClause) {
            throw new BadMethodCallException('WhereBuilder configuration methods cannot be accessed anymore once the where-clause is generated.');
        }

        if (!$this->searchCondition->getFieldSet()->has($fieldName)) {
            throw new UnknownFieldException($fieldName);
        }

        if ($converter instanceof ValueConversionInterface) {
            $this->valueConversions[$fieldName] = $converter;
        }

        if ($converter instanceof SqlFieldConversionInterface) {
            $this->fieldConversions[$fieldName] = $converter;
        }

        return $this;
    }

    /**
     * Set the prefix to prefix the query-parameters with.
     *
     * This will be applied as: prefix + fieldname + group + value-index.
     * Example: user_id_0_1
     *
     * @param string $prefix
     *
     * @return self
     *
     * @throws BadMethodCallException when the where-clause is already generated
     */
    public function setParameterPrefix($prefix)
    {
        if ($this->whereClause) {
            throw new BadMethodCallException('WhereBuilder configuration methods cannot be accessed anymore once the where-clause is generated.');
        }

        $this->parameterPrefix = $prefix;
    }

    /**
     * {@inheritdoc}
     */
    public function getParameters()
    {
        if ($this->queryGenerator) {
            return $this->queryGenerator->getParameters();
        }

        return array();
    }

    /**
     * Returns the parameter-value that where set during the generation process.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getParameter($name)
    {
        if ($this->queryGenerator) {
            return $this->queryGenerator->getParameter($name);
        }

        return null;
    }

    /**
     * @return SearchConditionInterface
     */
    public function getSearchCondition()
    {
        return $this->searchCondition;
    }

    /**
     * @return ConversionStrategyInterface[]|SqlValueConversionInterface[]|ValueConversionInterface[]
     */
    public function getValueConversions()
    {
        return $this->valueConversions;
    }

    /**
     * @return SqlFieldConversionInterface[]|ConversionStrategyInterface[]
     */
    public function getFieldConversions()
    {
        return $this->fieldConversions;
    }
}
