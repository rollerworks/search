<?php

/*
 * This file is part of the RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Exporter;

use Rollerworks\Component\Search\ExporterInterface;
use Rollerworks\Component\Search\FieldAliasResolverInterface;
use Rollerworks\Component\Search\FieldLabelResolverInterface;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\SearchConditionInterface;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\ValuesGroup;

/**
 * AbstractExporter provides the shared logic for the exporters.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
abstract class AbstractExporter implements ExporterInterface
{
    /**
     * @var FieldLabelResolverInterface
     */
    protected $labelResolver;

    /**
     * @param FieldAliasResolverInterface $aliasResolver
     */
    public function __construct(FieldAliasResolverInterface $aliasResolver)
    {
        $this->aliasResolver = $aliasResolver;
    }

    /**
     * Exports the SearchCondition.
     *
     * @param SearchConditionInterface $condition     The SearchCondition to export
     * @param bool                     $useFieldAlias Use the localized field-alias
     *                                                instead of the actual name (default false)
     *
     * @return mixed
     *
     * @throws \RuntimeException
     */
    public function exportCondition(SearchConditionInterface $condition, $useFieldAlias = false)
    {
        return $this->exportGroup($condition->getValuesGroup(), $condition->getFieldSet(), $useFieldAlias, true);
    }

    /**
     * @param PatternMatch $patternMatch
     *
     * @return string
     *
     * @throws \RuntimeException When an unsupported pattern-match type is found.
     */
    protected function getPatternMatchType(PatternMatch $patternMatch)
    {
        $type = '';

        if (
            in_array(
                $patternMatch->getType(),
                array(
                    PatternMatch::PATTERN_NOT_CONTAINS,
                    PatternMatch::PATTERN_NOT_STARTS_WITH,
                    PatternMatch::PATTERN_NOT_ENDS_WITH,
                    PatternMatch::PATTERN_NOT_REGEX,
                ), true
            )
        ) {
            $type .= 'NOT_';
        }

        switch ($patternMatch->getType()) {
            case PatternMatch::PATTERN_CONTAINS:
            case PatternMatch::PATTERN_NOT_CONTAINS:
                $type .= 'CONTAINS';
                break;

            case PatternMatch::PATTERN_STARTS_WITH:
            case PatternMatch::PATTERN_NOT_STARTS_WITH:
                $type .= 'STARTS_WITH';
                break;

            case PatternMatch::PATTERN_ENDS_WITH:
            case PatternMatch::PATTERN_NOT_ENDS_WITH:
                $type .= 'ENDS_WITH';
                break;

            case PatternMatch::PATTERN_REGEX:
            case PatternMatch::PATTERN_NOT_REGEX:
                $type .= 'REGEX';
                break;

            default:
                throw new \RuntimeException(
                    sprintf(
                        'Unsupported pattern-match type "%s" found. Please report this bug.',
                        $patternMatch->getType()
                    )
                );
        }

        return $type;
    }

    /**
     * @param ValuesGroup $valuesGroup
     * @param FieldSet    $fieldSet
     * @param bool        $useFieldAlias
     * @param bool        $isRoot
     *
     * @return mixed
     */
    abstract protected function exportGroup(ValuesGroup $valuesGroup, FieldSet $fieldSet, $useFieldAlias = false, $isRoot = false);
}
