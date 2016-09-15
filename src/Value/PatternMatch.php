<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Value;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class PatternMatch implements ValueHolder
{
    const PATTERN_CONTAINS = 1;
    const PATTERN_STARTS_WITH = 2;
    const PATTERN_ENDS_WITH = 3;
    const PATTERN_REGEX = 4;
    const PATTERN_NOT_CONTAINS = 5;
    const PATTERN_NOT_STARTS_WITH = 6;
    const PATTERN_NOT_ENDS_WITH = 7;
    const PATTERN_NOT_REGEX = 8;
    const PATTERN_EQUALS = 9;
    const PATTERN_NOT_EQUALS = 10;

    /**
     * @var string
     */
    private $value;

    /**
     * Comparison operator.
     *
     * @var string
     */
    private $patternType;

    /**
     * @var bool
     */
    private $caseInsensitive;

    /**
     * Constructor.
     *
     * @param string     $value
     * @param int|string $patternType
     * @param bool       $caseInsensitive
     *
     * @throws \InvalidArgumentException When the pattern-match type is invalid
     */
    public function __construct($value, $patternType, $caseInsensitive = false)
    {
        if (!is_scalar($value)) {
            throw new \InvalidArgumentException('Value of PatternMatch must be a scalar value.');
        }

        if (is_string($patternType)) {
            $typeConst = 'Rollerworks\\Component\\Search\\Value\\PatternMatch::PATTERN_'.strtoupper($patternType);

            if (defined($typeConst)) {
                $patternType = constant($typeConst);
            } else {
                $patternType = -1;
            }
        }

        if ($patternType < 1 || $patternType > 10) {
            throw new \InvalidArgumentException(sprintf('Unknown pattern-match type "%s".', $patternType));
        }

        $this->patternType = $patternType;
        $this->value = (string) $value;
        $this->caseInsensitive = $caseInsensitive;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Gets the pattern-match type.
     *
     * @return int
     */
    public function getType()
    {
        return $this->patternType;
    }

    /**
     * @return bool
     */
    public function isCaseInsensitive()
    {
        return $this->caseInsensitive;
    }

    /**
     * @return bool
     */
    public function isExclusive()
    {
        return in_array(
            $this->patternType,
            [
                self::PATTERN_NOT_STARTS_WITH,
                self::PATTERN_NOT_CONTAINS,
                self::PATTERN_NOT_ENDS_WITH,
                self::PATTERN_NOT_REGEX,
                self::PATTERN_NOT_EQUALS,
            ], true
        );
    }

    /**
     * @return bool
     */
    public function isRegex()
    {
        return self::PATTERN_REGEX === $this->patternType
            || self::PATTERN_NOT_REGEX === $this->patternType
        ;
    }
}
