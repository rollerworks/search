<?php

/*
 * This file is part of the RollerworksRecordFilterBundle.
 *
 * (c) Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rollerworks\Bundle\RecordFilterBundle\Doctrine\Orm;

use Rollerworks\Bundle\RecordFilterBundle\Formatter\FormatterInterface;
use Rollerworks\Bundle\RecordFilterBundle\FilterField;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type as ORMType;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\AbstractQuery as OrmQuery;
use Doctrine\ORM\Query as DqlQuery;

/**
 * RecordFilter Doctrine ORM QueryBuilderHelper.
 *
 * Adds the WhereBuilder generated DQL to the QueryBuilder.
 *
 * @see WhereBuilder
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class QueryWhereBuilderHelper
{
    protected $whereBuilder;

    /**
     * Add the WHERE clause to the QueryBuilder.
     *
     * This must be done "after" all JOIN's are set.
     * The clause is added as AND case.
     *
     * @param FormatterInterface             $formatter
     * @param WhereBuilder|CacheWhereBuilder $whereBuilder        Either an WhereBuilder or CacheWhereBuilder instance.
     * @param QueryBuilder                   $queryBuilder
     * @param array                          $entityAliasMapping  An array with the alias-mapping as [class or Bundle:Class] => entity-alias
     * @param boolean                        $resetParameterIndex Set to false if you want to keep the parameter index when calling this method again.
     *                                                            This should only be used when using multiple filtering results in the same query.
     *
     * @throws \InvalidArgumentException when the $whereBuilder is not an instance of WhereBuilder or CacheWhereBuilder
     */
    public function addWhereToQueryBuilder(FormatterInterface $formatter, $whereBuilder, QueryBuilder $queryBuilder, array $entityAliasMapping = array(), $resetParameterIndex = true)
    {
        if (!$whereBuilder instanceof WhereBuilder && !$whereBuilder instanceof CacheWhereBuilder) {
            throw new \InvalidArgumentException('$whereBuilder must be instance of WhereBuilder or CacheWhereBuilder.');
        }

        // When no mapping is set make it from the QueryBuilder
        // Unfortunately its not possible to safely do this for Joining
        if (!$entityAliasMapping) {
            $rootAliases = $queryBuilder->getRootAliases();

            foreach ($queryBuilder->getRootEntities() as $i => $entity) {
                $entityAliasMapping[$entity] = $rootAliases[$i];
            }
        }

        if ($whereBuilder instanceof CacheWhereBuilder) {
            $whereCase = $whereBuilder->getWhereClause($formatter, null, $entityAliasMapping, $queryBuilder, null, $resetParameterIndex);
        } else {
            $whereCase = $whereBuilder->getWhereClause($formatter, $entityAliasMapping, $queryBuilder, null, $resetParameterIndex);
        }

        if ($whereCase) {
            $queryBuilder->andWhere($whereCase);
        }

        if ($whereBuilder instanceof CacheWhereBuilder) {
            $this->whereBuilder = $whereBuilder->getInnerWhereBuilder();
        } else {
            $this->whereBuilder = $whereBuilder;
        }
    }

    /**
     * Returns the Query hint name for the final query object.
     *
     * The Query hint is used for conversions.
     *
     * @return string
     */
    public function getQueryHintName()
    {
        return 'where_builder_conversions';
    }

    /**
     * Returns the Query hint value for the final query object.
     *
     * The Query hint is used for conversions.
     *
     * @return \Closure
     */
    public function getQueryHintValue()
    {
        $self = $this->whereBuilder;

        return function () use (&$self) { return $self; };
    }
}
