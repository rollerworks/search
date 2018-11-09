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

namespace Rollerworks\Component\Search\Elasticsearch;

use Elastica\Query;
use Rollerworks\Component\Search\SearchCondition;

/**
 * ConditionGenerator.
 *
 * Sample usage with Elastica:
 *
 * ```
 * <?php
 * $search = new \Elastica\Search();
 *
 * // TODO: properly create a search condition here
 * $searchCondition = \Rollerworks\Component\SearchSearchCondition();
 *
 * $generator = Rollerworks\Component\Search\Elasticsearch\QueryConditionGenerator($searchCondition);
 * $generator
 *     ->registerField('fieldset-alias', '/elasticsearch-index/elasticsearch-type#elasticsearch.property');
 *
 * $mappings = $generator->getMappings();
 * foreach ($mappings as $mapping) {
 *     $search
 *         ->addIndex($mapping->indexName)
 *         ->addType($mapping->typeName);
 * }
 *
 * $query = $generator->getQuery();
 * $results = $search->search($query);
 * ```
 */
interface ConditionGenerator
{
    /**
     * Supported Elasticsearch property mapping formats:
     *     - <property>
     *     - <sub.property>
     *     - <nested[].property>
     *     - <sub.nested[].property>
     *     - <index>#<property>
     *     - <index>#<nested[].property>
     *     - <index>#<sub.nested[].property>
     *     - <index>/<type>#<property>
     *     - <index>/<type>#<sub.nested[].property>
     * with object dot-notation and [] indicating nested objects.
     *
     * See references for objects and nested objects.
     *
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/object.html
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/nested.html
     * @see https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-nested-query.html
     *
     * @param string $fieldName Field set name
     * @param string $mapping   Elasticsearch property mapping
     *
     * @return static
     */
    public function registerField(string $fieldName, string $mapping);

    /**
     * Return a valid Elastica\Query search query. Query can be sent to a _search endpoint as is.
     */
    public function getQuery(): Query;

    /**
     * Return mappings actually used in the query. It allows to restrict the query on specific indices/types.
     *
     * @return FieldMapping[]
     */
    public function getMappings(): array;

    /**
     * Returns the assigned SearchCondition.
     *
     * @internal
     */
    public function getSearchCondition(): SearchCondition;
}
