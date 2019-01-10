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

use Rollerworks\Component\Search\Field\FieldConfig;
use Rollerworks\Component\Search\Field\OrderField;

/** @internal */
final class FieldMapping implements \Serializable
{
    public $fieldName;
    public $indexName;
    public $typeName;
    public $propertyName;
    public $propertyValue;
    public $propertyQuery;
    public $nested = false;
    public $join = false;
    public $boost;

    /**
     * @var ValueConversion
     */
    public $valueConversion;

    /**
     * @var QueryConversion
     */
    public $queryConversion;

    /**
     * @var self[]
     */
    public $conditions;

    /**
     * @var array
     */
    public $options;

    public function __construct(string $fieldName, string $property, FieldConfig $fieldConfig, array $conditions = [], array $options = [])
    {
        $this->fieldName = $fieldName;

        $mapping = $this->parseProperty($property);
        $this->indexName = $mapping['indexName'];
        $this->typeName = $mapping['typeName'];
        $this->propertyName = $mapping['propertyName'];
        $this->nested = $mapping['nested'];
        $this->join = $mapping['join'];

        $this->conditions = $this->expandConditions($conditions, $fieldConfig);
        $this->options = $options;

        $converter = $fieldConfig->getOption('elasticsearch_conversion');

        if ($converter instanceof ValueConversion) {
            $this->valueConversion = $converter;
        }
        if ($converter instanceof QueryConversion) {
            $this->queryConversion = $converter;
        }
    }

    /**
     * Supported formats:
     *      - <property>
     *      - <sub.property>
     *      - <nested[].property>
     *      - <sub.nested[].property>
     *      - <index>#<property>
     *      - <index>#<nested[].property>
     *      - <index>#<sub.nested[].property>
     *      - <index>/<type>#<property>
     *      - <index>/<type>#<sub.nested[].property>
     *      - <index>/<type>#child><sub.nested[].property>.
     *
     * @return string[]
     */
    private function parseProperty(string $property): array
    {
        $indexName = null;
        $typeName = null;
        $propertyName = $property;
        $nested = false;
        $join = false;
        if (false !== strpos($property, '#')) {
            [$path, $propertyName] = explode('#', $property);

            $path = trim($path, '/');
            $indexName = $path;
            if (false !== strpos($path, '/')) {
                [$indexName, $typeName] = explode('/', $path);
            }
        }

        if (false !== strpos($property, '>')) {
            $tokens = explode('>', $propertyName);

            // last token is the property name
            $propertyName = trim(array_pop($tokens), '.');

            foreach ($tokens as $type) {
                $type = trim($type, '.');
                $join = \compact('type', 'join');
            }
        }

        if (false !== strpos($propertyName, '[]')) {
            $tokens = explode('[]', $propertyName);

            // last token is the property name
            $propertyName = trim(array_pop($tokens), '.');
            $propertyName = trim(end($tokens), '.').'.'.$propertyName;

            foreach ($tokens as $path) {
                $path = trim($path, '.');
                $nested = \compact('path', 'nested');
            }
        }

        return compact('indexName', 'typeName', 'propertyName', 'nested', 'join');
    }

    private function expandConditions(array $conditions, FieldConfig $fieldConfig): array
    {
        if (OrderField::isOrder($this->fieldName) && $this->join) {
            // sorting by has_child query is special
            // https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-has-child-query.html#_sorting
            $property = $this->indexName.($this->typeName ? '/'.$this->typeName : '').'#'.$this->join['type'].'>';

            $scoreQuery = new self('_', $property, $fieldConfig, [], ['score_mode' => 'max']);
            $scoreQuery->propertyQuery = [
                QueryConditionGenerator::QUERY_FUNCTION_SCORE => [
                    QueryConditionGenerator::QUERY_SCRIPT_SCORE => [
                        QueryConditionGenerator::QUERY_SCRIPT => sprintf('%1$s * doc["%2$s"].value', QueryConditionGenerator::SORT_SCORE, $this->propertyName),
                    ],
                ],
            ];
            $conditions[] = $scoreQuery;
        }

        return $conditions;
    }

    public function serialize()
    {
        return serialize(
            [
                'field_name' => $this->fieldName,
                'index_name' => $this->indexName,
                'type_name' => $this->typeName,
                'property_name' => $this->propertyName,
                'nested' => $this->nested,
            ]
        );
    }

    public function unserialize($serialized): void
    {
        // no-op
    }
}
