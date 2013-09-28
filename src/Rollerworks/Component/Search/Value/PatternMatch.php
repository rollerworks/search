<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search\Value;

class PatternMatch
{
    const PATTERN_CONTAINS = 1;
    const PATTERN_STARTS_WITH = 2;
    const PATTERN_ENDS_WITH = 3;
    const PATTERN_REGEX = 4;
    const PATTERN_NOT_CONTAINS = 5;
    const PATTERN_NOT_STARTS_WITH = 6;
    const PATTERN_NOT_ENDS_WITH = 7;
    const PATTERN_NOT_REGEX = 8;

    /**
     * @var mixed
     */
    protected $value;

    /**
     * @var mixed
     */
    protected $viewValue;

    /**
     * Comparison operator.
     *
     * @var string
     */
    protected $patternType;

    /**
     * @var boolean
     */
    protected $caseInsensitive;

    /**
     * Constructor.
     *
     * @param string         $value
     * @param integer|string $patternType
     * @param boolean        $caseInsensitive
     *
     * @throws \InvalidArgumentException When the pattern-match type is invalid.
     */
    public function __construct($value, $patternType, $caseInsensitive = false)
    {
        if (!is_scalar($value)) {
            throw new \InvalidArgumentException('Value of PatternMatch must be a scalar value.');
        }

        if (is_string($patternType)) {
            if (defined('Rollerworks\\Component\\Search\\Value\\PatternMatch::PATTERN_' . strtoupper($patternType))) {
                $patternType = constant('Rollerworks\\Component\\Search\\Value\\PatternMatch::PATTERN_' . strtoupper($patternType));
            } else {
                $patternType = -1;
            }
        }

        if ($patternType < 1 || $patternType > 8) {
            throw new \InvalidArgumentException('Unknown pattern-match type.');
        }

        $this->patternType = $patternType;
        $this->value = $value;
        $this->viewValue = $value;
        $this->caseInsensitive = $caseInsensitive;
    }

    /**
     * @param string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     *
     * @return string
     */
    public function setViewValue($value)
    {
        $this->viewValue = $value;
    }

    /**
     * @return string
     */
    public function getViewValue()
    {
        return $this->value;
    }

    /**
     * Gets the pattern-match type.
     *
     * @return integer
     */
    public function getType()
    {
        return $this->patternType;
    }

    /**
     * @param boolean $caseInsensitive
     */
    public function setCaseInsensitive($caseInsensitive = true)
    {
        $this->caseInsensitive = $caseInsensitive;
    }

    /**
     * @return boolean
     */
    public function isCaseInsensitive()
    {
        return $this->caseInsensitive;
    }
}
