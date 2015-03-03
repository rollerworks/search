<?php
/**
 * PhpStorm.
 */

namespace Rollerworks\Component\Search\Doctrine\Dbal;

use Rollerworks\Component\Search\Value\PatternMatch;

interface QueryPlatformInterface
{
    /**
     * Returns the correct column (with SQLField conversions applied).
     *
     * @param string $fieldName
     * @param int    $strategy
     *
     * @return string
     */
    public function getFieldColumn($fieldName, $strategy = 0);

    /**
     * Returns either the converted value.
     *
     * @param string $value
     * @param string $fieldName
     * @param string $column
     * @param int    $strategy
     *
     * @return string
     */
    public function getValueAsSql($value, $fieldName, $column, $strategy = 0);

    /**
     * Returns the formatted PatternMatch query.
     *
     * @param PatternMatch $patternMatch
     * @param string       $column
     *
     * @return string Some like: u.name LIKE '%foo%'
     */
    public function getPatternMatcher(PatternMatch $patternMatch, $column);
}
