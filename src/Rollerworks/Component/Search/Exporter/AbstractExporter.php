<?php

/*
 * This file is part of the RollerworksRecordFilterBundle package.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Component\Search\Exporter;

use Rollerworks\Component\Search\ExporterInterface;
use Rollerworks\Component\Search\FieldLabelResolverInterface;
use Rollerworks\Component\Search\FieldSet;
use Rollerworks\Component\Search\SearchConditionInterface;
use Rollerworks\Component\Search\ValuesGroup;

/**
 * AbstractExporter provides the shared logic for the exporters.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
abstract class AbstractExporter implements ExporterInterface
{
    /**
     * @var FieldLabelResolverInterface|null
     */
    protected $labelResolver;

    /**
     * Set the label resolver for resolving name to localized-alias.
     *
     * @param FieldLabelResolverInterface $resolver
     *
     * @return self
     */
    public function setLabelResolver(FieldLabelResolverInterface $resolver = null)
    {
        $this->labelResolver = $resolver;

        return $this;
    }

    /**
     * Get the label resolver.
     *
     * @return FieldLabelResolverInterface|null Returns null when none is set
     */
    public function getLabelResolver()
    {
        return $this->labelResolver;
    }

    /**
     * Exports the SearchCondition.
     *
     * @param SearchConditionInterface $condition     The SearchCondition to export
     * @param boolean                  $useFieldAlias Use the localized field-alias instead of the actual name (default false)
     *
     * @return mixed
     *
     * @throws \RuntimeException
     */
    public function exportCondition(SearchConditionInterface $condition, $useFieldAlias = false)
    {
        if ($useFieldAlias && null === $this->labelResolver) {
            throw new \RuntimeException('Unable resolve field-name to alias because no labelResolver is configured.');
        }

        return $this->exportGroup($condition->getValuesGroup(), $condition->getFieldSet(), $useFieldAlias, true);
    }

    /**
     * @param ValuesGroup $valuesGroup
     * @param FieldSet    $fieldSet
     * @param boolean     $useFieldAlias
     * @param boolean     $isRoot
     *
     * @return mixed
     */
    abstract protected function exportGroup(ValuesGroup $valuesGroup, FieldSet $fieldSet, $useFieldAlias = false, $isRoot = false);
}
