<?php

/**
 * This file is part of RollerworksSearch Component package.
 *
 * (c) 2012-2014 Sebastiaan Stok <s.stok@rollerscapes.net>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Rollerworks\Component\Search\Extension\Doctrine\Orm\Conversion;

use Rollerworks\Component\Search\Doctrine\Dbal\SqlFieldConversionInterface;
use Rollerworks\Component\Search\FieldConfigInterface;

/**
 * EntityCountConversion.
 *
 * Allows counting the number of parent/children references.
 *
 * @author Sebastiaan Stok <s.stok@rollerscapes.net>
 */
class EntityCountConversion implements SqlFieldConversionInterface
{
    /**
     * {@inheritdoc}
     */
    public function convertSqlField($column, array $options, array $hints)
    {
        /* @var \Doctrine\ORM\EntityManager $em */
        $em = $hints['entity_manager'];
        /** @var FieldConfigInterface $field */
        $field = $hints['search_field'];

        // Note we do the table/field -name resolving here and not in the type
        // The options require the EntityManager which is not available until
        // all options are resolved

        if (null === $options['table_name']) {
            $refClassMeta = $em->getClassMetadata($em->getClassMetadata($field->getModelRefClass())
                ->getAssociationTargetClass($field->getModelRefProperty()));

            $options['table_name'] = $refClassMeta->getTableName();
        }

        if (null === $options['table_field']) {
            $classMetadata = $em->getClassMetadata($field->getModelRefClass());
            $options['table_field'] = $classMetadata->getFieldForColumn($field->getModelRefProperty());
        }

        return "(SELECT COUNT(*) FROM " . $options['table_name'] . " WHERE " . $options['table_field'] . " = $column)";
    }
}
