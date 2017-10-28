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

use Rollerworks\Component\Search\Exception\InvalidArgumentException;

/**
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
final class PatternMatch implements ValueHolder
{
    public const PATTERN_CONTAINS = 'CONTAINS';
    public const PATTERN_STARTS_WITH = 'STARTS_WITH';
    public const PATTERN_ENDS_WITH = 'ENDS_WITH';
    public const PATTERN_NOT_CONTAINS = 'NOT_CONTAINS';
    public const PATTERN_NOT_STARTS_WITH = 'NOT_STARTS_WITH';
    public const PATTERN_NOT_ENDS_WITH = 'NOT_ENDS_WITH';
    public const PATTERN_EQUALS = 'EQUALS';
    public const PATTERN_NOT_EQUALS = 'NOT_EQUALS';

    /**
     * @var string
     */
    private $value;

    /**
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
            throw new InvalidArgumentException(sprintf('Unknown PatternMatch type "%s".', $patternType));
        }

        $this->value = $value;
        $this->patternType = strtoupper($patternType);
        $this->caseInsensitive = $caseInsensitive;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getType(): string
    {
        return $this->patternType;
    }

    public function isCaseInsensitive(): bool
    {
        return $this->caseInsensitive;
    }

    public function isExclusive(): bool
    {
        return in_array(
            $this->patternType,
            [
                self::PATTERN_NOT_STARTS_WITH,
                self::PATTERN_NOT_CONTAINS,
                self::PATTERN_NOT_ENDS_WITH,
                self::PATTERN_NOT_EQUALS,
            ], true
        );
    }
}
