<?php

/*
 * This file is part of the RollerworksSearch package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Exporter;

use Rollerworks\Component\Search\ExporterInterface;
use Rollerworks\Component\Search\FieldLabelResolver\NoopLabelResolver;
use Rollerworks\Component\Search\FieldLabelResolverInterface;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\SearchConditionInterface;
use Rollerworks\Component\Search\Value\PatternMatch;
use Rollerworks\Component\Search\ValuesGroup;

/**
 * AbstractExporter provides the shared logic for the condition exporters.
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
     * @param FieldLabelResolverInterface $labelResolver
     */
    public function __construct(FieldLabelResolverInterface $labelResolver)
    {
        $this->labelResolver = $labelResolver;
    }

    /**
     * Exports a search condition.
     *
     * @param SearchConditionInterface $condition     The search condition to export
     * @param bool                     $useFieldAlias Use the localized field-alias
     *                                                instead of the actual name (default false)
     *
     * @throws \RuntimeException
     *
     * @return mixed
     */
    public function exportCondition(SearchConditionInterface $condition, $useFieldAlias = false)
    {
        $labelResolver = $this->labelResolver;

        if (!$useFieldAlias && $this->labelResolver instanceof NoopLabelResolver) {
            $this->labelResolver = new NoopLabelResolver();
        }

        $result = $this->exportGroup($condition->getValuesGroup(), $condition->getFieldSet(), true);

        // Restore original resolver.
        $this->labelResolver = $labelResolver;

        return $result;
    }

    /**
     * @param PatternMatch $patternMatch
     *
     * @throws \RuntimeException When an unsupported pattern-match type is provided
     *
     * @return string
     */
    protected function getPatternMatchType(PatternMatch $patternMatch)
    {
        $type = '';

        if ($patternMatch->isExclusive()) {
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

            case PatternMatch::PATTERN_EQUALS:
            case PatternMatch::PATTERN_NOT_EQUALS:
                $type .= 'EQUALS';
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
     * @param bool        $isRoot
     *
     * @return mixed
     */
    abstract protected function exportGroup(ValuesGroup $valuesGroup, FieldSet $fieldSet, $isRoot = false);
}
