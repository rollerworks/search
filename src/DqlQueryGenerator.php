<?php

/*
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Doctrine\Orm;

use Rollerworks\Component\Search\Doctrine\Dbal\QueryGenerator;
use Rollerworks\Component\Search\Doctrine\Dbal\SqlFieldConversionInterface;
use Rollerworks\Component\Search\Doctrine\Dbal\SqlValueConversionInterface;
use Rollerworks\Component\Search\FieldConfigInterface;
use Rollerworks\Component\Search\Value\PatternMatch;

class DqlQueryGenerator extends QueryGenerator
{
    /**
     * {@inheritdoc}
     */
    protected function getPatternMatcher(PatternMatch $patternMatch, $column, $value)
    {
        // Doctrine at the moment does not support case insensitive LIKE or regex match
        // So we use a custom function for this

        $pattern = array(
            PatternMatch::PATTERN_STARTS_WITH => "RW_SEARCH_MATCH(%s, %s, 'starts_with', %s) = 1",
            PatternMatch::PATTERN_NOT_STARTS_WITH => "RW_SEARCH_MATCH(%s, %s', 'starts_with', %s) <> 1",

            PatternMatch::PATTERN_CONTAINS => "RW_SEARCH_MATCH(%s, %s, 'contains', %s) = 1",
            PatternMatch::PATTERN_NOT_CONTAINS => "RW_SEARCH_MATCH(%s, %s, 'contains', %s) <> 1",

            PatternMatch::PATTERN_ENDS_WITH => "RW_SEARCH_MATCH(%s, %s, 'ends_with', %s) = 1",
            PatternMatch::PATTERN_NOT_ENDS_WITH => "RW_SEARCH_MATCH(%s, %s, 'ends_with', %s) <> 1",

            PatternMatch::PATTERN_REGEX => "RW_SEARCH_MATCH(%s, %s, 'regex', %s) = 1",
            PatternMatch::PATTERN_NOT_REGEX => "RW_SEARCH_MATCH(%s, %s, 'regex', %s) <> 1",
        );

        return sprintf(
            $pattern[$patternMatch->getType()],
            $column,
            $value,
            ($patternMatch->isCaseInsensitive() ? 'true' : 'false')
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function convertSqlValue(SqlValueConversionInterface $converter, $fieldName, $column, $value, $convertedValue, FieldConfigInterface $field, array $hints, $strategy)
    {
        // If the value requires embedding we inform the DQL function about the parameter-index
        // Where he can then find the value, else its not possible to embed the value safely
        $valueRequiresEmbedding = $converter->valueRequiresEmbedding($value, $field->getOptions(), $hints);

        $paramName = $this->getUniqueParameterName($fieldName);
        $this->parameters[$paramName] = $convertedValue;
        $convertedValue = ':'.$paramName;

        return sprintf(
            'RW_SEARCH_VALUE_CONVERSION(\'%s\', %s, %s, %s, %s)',
            $fieldName,
            $this->fields[$fieldName]['column'],
            $convertedValue,
            (null === $strategy ? 'null' : $strategy),
            ($valueRequiresEmbedding ? 'true' : 'false')
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getFieldColumn($fieldName, $strategy = null)
    {
        if (isset($this->fieldsMappingCache[$fieldName]) && array_key_exists($strategy, $this->fieldsMappingCache[$fieldName])) {
            return $this->fieldsMappingCache[$fieldName][$strategy];
        }

        if (!isset($this->fieldsMappingCache[$fieldName])) {
            $this->fieldsMappingCache[$fieldName] = array();
        }

        if ($this->fields[$fieldName]['field_convertor'] instanceof SqlFieldConversionInterface) {
            $this->fieldsMappingCache[$fieldName][$strategy] = sprintf(
                'RW_SEARCH_FIELD_CONVERSION(\'%s\', %s, %s)',
                $fieldName,
                $this->fields[$fieldName]['column'],
                (null === $strategy ? 'null' : $strategy)
            );
        } else {
            $this->fieldsMappingCache[$fieldName][$strategy] = $this->fields[$fieldName]['column'];
        }

        return $this->fieldsMappingCache[$fieldName][$strategy];
    }
}
