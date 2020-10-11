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

namespace Rollerworks\Component\Search\Doctrine\Orm\QueryPlatform;

use Doctrine\DBAL\Types\Type;
use Rollerworks\Component\Search\Doctrine\Dbal\QueryPlatform\AbstractQueryPlatform;
use Rollerworks\Component\Search\Value\PatternMatch;

final class DqlQueryPlatform extends AbstractQueryPlatform
{
    public function getPatternMatcher(PatternMatch $patternMatch, string $column): string
    {
        if (\in_array($patternMatch->getType(), [PatternMatch::PATTERN_EQUALS, PatternMatch::PATTERN_NOT_EQUALS], true)) {
            $value = $this->createParamReferenceFor($patternMatch->getValue(), Type::getType('text'));

            if ($patternMatch->isCaseInsensitive()) {
                $column = "LOWER({$column})";
                $value = "LOWER({$value})";
            }

            return $column . ($patternMatch->isExclusive() ? ' <>' : ' =') . " {$value}";
        }

        $patternMap = [
            PatternMatch::PATTERN_STARTS_WITH => "CONCAT('%%', %s)",
            PatternMatch::PATTERN_NOT_STARTS_WITH => "CONCAT('%%', %s)",
            PatternMatch::PATTERN_CONTAINS => "CONCAT('%%', %s, '%%')",
            PatternMatch::PATTERN_NOT_CONTAINS => "CONCAT('%%', %s, '%%')",
            PatternMatch::PATTERN_ENDS_WITH => "CONCAT(%s, '%%')",
            PatternMatch::PATTERN_NOT_ENDS_WITH => "CONCAT(%s, '%%')",
        ];

        $value = \addcslashes($patternMatch->getValue(), $this->getLikeEscapeChars());
        $value = \sprintf($patternMap[$patternMatch->getType()], $this->createParamReferenceFor($value, Type::getType('text')));

        if ($patternMatch->isCaseInsensitive()) {
            $column = "LOWER({$column})";
            $value = "LOWER({$value})";
        }

        return $column . ($patternMatch->isExclusive() ? ' NOT' : '') . " LIKE {$value}";
    }
}
