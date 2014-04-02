<?php

/*
 * This file is part of the Rollerworks Search Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search\Doctrine\Orm;

use Rollerworks\Component\Search\Doctrine\Dbal\QueryGenerator;
use Rollerworks\Component\Search\Doctrine\Dbal\SqlFieldConversionInterface;
use Rollerworks\Component\Search\FieldConfigInterface;
use Rollerworks\Component\Search\Value\PatternMatch;

class DqlQueryGenerator extends QueryGenerator
{
    /**
     * Returns whether the field is accepted for processing.
     *
     * @param FieldConfigInterface $field
     *
     * @return boolean
     */
    protected function acceptsField(FieldConfigInterface $field)
    {
        return null !== $field->getModelRefClass();
    }

    /**
     * {@inheritdoc}
     */
    protected function getPatternMatcher(PatternMatch $patternMatch, $column, $value)
    {
        // Doctrine at the moment does not support case insensitive LIKE or regex match
        // So we use a custom function for this

        $pattern = array(
            PatternMatch::PATTERN_STARTS_WITH => "RW_SEARCH_MATCH(%s, %s, 'starts_with', " . ($patternMatch->isCaseInsensitive() ? 'true' : 'false') .") = 1",
            PatternMatch::PATTERN_NOT_STARTS_WITH => "RW_SEARCH_MATCH(%s, %s', 'starts_with', " . ($patternMatch->isCaseInsensitive() ? 'true' : 'false') .") <> 1",

            PatternMatch::PATTERN_CONTAINS => "RW_SEARCH_MATCH(%s, %s, 'contains', " . ($patternMatch->isCaseInsensitive() ? 'true' : 'false') .") = 1",
            PatternMatch::PATTERN_NOT_CONTAINS => "RW_SEARCH_MATCH(%s, %s, 'contains', " . ($patternMatch->isCaseInsensitive() ? 'true' : 'false') .") <> 1",

            PatternMatch::PATTERN_ENDS_WITH => "RW_SEARCH_MATCH(%s, %s, 'ends_with', " . ($patternMatch->isCaseInsensitive() ? 'true' : 'false') .") = 1",
            PatternMatch::PATTERN_NOT_ENDS_WITH => "RW_SEARCH_MATCH(%s, %s, 'ends_with', " . ($patternMatch->isCaseInsensitive() ? 'true' : 'false') .") <> 1",

            PatternMatch::PATTERN_REGEX => "RW_SEARCH_MATCH(%s, %s, 'regex', " . ($patternMatch->isCaseInsensitive() ? 'true' : 'false') .") = 1",
            PatternMatch::PATTERN_NOT_REGEX => "RW_SEARCH_MATCH(%s, %s, 'regex', " . ($patternMatch->isCaseInsensitive() ? 'true' : 'false') .") <> 1",
        );

        return sprintf($pattern[$patternMatch->getType()], $column, $value);
    }

    /**
     * {@inheritdoc}
     */
    protected function getFieldColumn($fieldName, $strategy = null)
    {
        if (isset($this->fieldsMappingCache[$fieldName]) && array_key_exists($strategy, $this->fieldsMappingCache[$fieldName])) {
            return $this->fieldsMappingCache[$fieldName][$strategy];
        }

        if ($this->fields[$fieldName]['field_convertor'] instanceof SqlFieldConversionInterface) {
            $this->fieldsMappingCache[$fieldName][$strategy] = "RW_SEARCH_FIELD_CONVERSION('$fieldName', $this->fields[$fieldName]['column'], " . (null === $strategy ? 'null' : $strategy) . ")";
        } else {
            $this->fieldsMappingCache[$fieldName][$strategy] = $this->fields[$fieldName]['column'];
        }

        return $this->fieldsMappingCache[$fieldName][$strategy];
    }
}
