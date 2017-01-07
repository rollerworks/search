<?php

declare(strict_types=1);

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
    const PATTERN_CONTAINS = 'CONTAINS';
    const PATTERN_STARTS_WITH = 'STARTS_WITH';
    const PATTERN_ENDS_WITH = 'ENDS_WITH';
    const PATTERN_REGEX = 'REGEX';
    const PATTERN_NOT_CONTAINS = 'NOT_CONTAINS';
    const PATTERN_NOT_STARTS_WITH = 'NOT_STARTS_WITH';
    const PATTERN_NOT_ENDS_WITH = 'NOT_ENDS_WITH';
    const PATTERN_NOT_REGEX = 'NOT_REGEX';
    const PATTERN_EQUALS = 'EQUALS';
    const PATTERN_NOT_EQUALS = 'NOT_EQUALS';

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
    public function __construct(string $value, string $patternType, bool $caseInsensitive = false)
    {
        $typeConst = __CLASS__.'::PATTERN_'.strtoupper($patternType);

        if (!defined($typeConst)) {
            throw new \InvalidArgumentException(sprintf('Unknown PatternMatch type "%s".', $patternType));
        }

        $this->value = $value;
        $this->patternType = strtoupper($patternType);
        $this->caseInsensitive = $caseInsensitive;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * Gets the pattern-match type.
     *
     * @return string
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
